<?php
/**
 * saas_analyze.php  —  SaaS / multi-tenant readiness analyzer
 *
 * Purpose: inspect a hand-rolled PHP project and report what actually matters
 * for converting it into a multi-tenant SaaS POS:
 *   - real composer dependencies (not keyword guessing)
 *   - database schema: every table + columns, which already have a tenant column,
 *     which business tables are missing one, schema drift (dump vs migrations),
 *     and duplicated/competing subsystems
 *   - query & security patterns: prepared statements vs concatenated SQL,
 *     a base model, raw $_GET/$_POST flowing into SQL
 *   - feature inventory & reusability
 *   - an explicit "share these next" list
 *
 * Usage:  php saas_analyze.php [project_root]
 *         (defaults to the directory this script sits in)
 *
 * No external dependencies. PHP 7.4+.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$ROOT = $argv[1] ?? __DIR__;
$ROOT = rtrim(str_replace('\\', '/', $ROOT), '/');
if (!is_dir($ROOT)) {
    fwrite(STDERR, "ERROR: not a directory: $ROOT\n");
    exit(1);
}

/* Directories we never want to walk into for code analysis. */
$SKIP_DIRS = ['vendor', 'node_modules', '.git', '.idea', '.vscode', 'uploads'];
/* Binary / noise extensions we ignore entirely. */
$BINARY_EXT = ['png','jpg','jpeg','gif','webp','ico','zip','rar','7z','pdf',
               'woff','woff2','ttf','eot','mp4','mov','bak','lock','map'];

/* ---- Tables that are platform-global (NOT per-tenant). Everything else that
   looks like business data is a candidate for tenant scoping. ---- */
$GLOBAL_TABLES = [
    'tenants','tenant_otp','subscription_plans','subscriptions','subscription_stk',
    'roles','migrations','login_attempts','password_resets',
];
/* Column names that indicate a table is already tenant/branch scoped. */
$TENANT_COL_HINTS = ['tenant_id','tenant','store_id','branch_id','company_id','org_id','account_id'];

/* ------------------------------------------------------------------ helpers */

function rel(string $path, string $root): string {
    $path = str_replace('\\', '/', $path);
    return ltrim(substr($path, strlen($root)), '/');
}

function should_skip_dir(string $name, array $skip): bool {
    return in_array(strtolower($name), $skip, true);
}

/** Recursively collect files, skipping $SKIP_DIRS. */
function walk(string $root, array $skipDirs): array {
    $out = [];
    $rii = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            function ($current) use ($skipDirs) {
                if ($current->isDir()) {
                    return !should_skip_dir($current->getFilename(), $skipDirs);
                }
                return true;
            }
        ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($rii as $file) {
        if ($file->isFile()) $out[] = $file->getPathname();
    }
    return $out;
}

/* ------------------------------------------------------------- gather files */

$allFiles = walk($ROOT, $SKIP_DIRS);

$byExt = [];
$codeFiles = [];   // php files we will read for pattern analysis (excludes vendor)
$sqlFiles = [];
$totalLoc = 0;

foreach ($allFiles as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $byExt[$ext] = ($byExt[$ext] ?? 0) + 1;
    if (in_array($ext, $BINARY_EXT, true)) continue;
    if ($ext === 'php') {
        $codeFiles[] = $f;
    } elseif ($ext === 'sql') {
        $sqlFiles[] = $f;
    }
}

/* LOC for our own code only (cap file size read to stay fast/safe). */
foreach ($codeFiles as $f) {
    $size = @filesize($f);
    if ($size === false || $size > 3_000_000) continue;
    $lines = @file($f, FILE_IGNORE_NEW_LINES);
    if ($lines !== false) $totalLoc += count($lines);
}

/* ------------------------------------------------- real composer dependencies */

$dependencies = ['require' => [], 'require-dev' => [], 'found' => false];
$composerPath = $ROOT . '/composer.json';
if (is_file($composerPath)) {
    $json = json_decode((string)@file_get_contents($composerPath), true);
    if (is_array($json)) {
        $dependencies['found'] = true;
        $dependencies['require'] = $json['require'] ?? [];
        $dependencies['require-dev'] = $json['require-dev'] ?? [];
    }
}

/* ----------------------------------------------------- parse SQL schema */

/**
 * Split a string on commas that are NOT inside parentheses or quotes.
 * Handles DECIMAL(10,2), ENUM('a','b'), etc.
 */
function split_top_level_commas(string $s): array {
    $out = []; $buf = ''; $depth = 0; $q = null; $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if ($q !== null) {                 // inside a quote
            $buf .= $c;
            if ($c === $q && ($i === 0 || $s[$i-1] !== '\\')) $q = null;
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
        if ($c === '(') { $depth++; $buf .= $c; continue; }
        if ($c === ')') { $depth = max(0, $depth - 1); $buf .= $c; continue; }
        if ($c === ',' && $depth === 0) { $out[] = $buf; $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = $buf;
    return $out;
}

/**
 * Extract CREATE TABLE blocks → table => [columns...] and remember the source file.
 * Regex-based; adequate for standard mysqldump / migration DDL.
 */
function parse_sql_schema(array $sqlFiles, string $root): array {
    $tables = []; // name => ['columns'=>[], 'files'=>[]]
    foreach ($sqlFiles as $f) {
        $sql = (string)@file_get_contents($f);
        if ($sql === '') continue;
        $relf = rel($f, $root);

        if (preg_match_all(
            '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([A-Za-z0-9_]+)[`"]?\s*\((.*?)\)\s*(?:ENGINE|DEFAULT|COMMENT|;)/is',
            $sql, $m, PREG_SET_ORDER
        )) {
            foreach ($m as $match) {
                $table = strtolower($match[1]);
                $body  = $match[2];
                $cols  = [];
                // Split column definitions on top-level commas (ignoring commas
                // inside parentheses like DECIMAL(10,2) or ENUM('a','b')).
                foreach (split_top_level_commas($body) as $frag) {
                    $frag = trim($frag);
                    if ($frag === '') continue;
                    // skip constraint / key lines
                    if (preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|FOREIGN|FULLTEXT|SPATIAL)\b/i', $frag)) continue;
                    if (preg_match('/^[`"]?([A-Za-z0-9_]+)[`"]?\s+/', $frag, $cm)) {
                        $cols[] = strtolower($cm[1]);
                    }
                }
                if (!isset($tables[$table])) $tables[$table] = ['columns' => [], 'files' => []];
                // union columns across definitions
                $tables[$table]['columns'] = array_values(array_unique(array_merge($tables[$table]['columns'], $cols)));
                if (!in_array($relf, $tables[$table]['files'], true)) $tables[$table]['files'][] = $relf;
            }
        }
    }
    ksort($tables);
    return $tables;
}

$tables = parse_sql_schema($sqlFiles, $ROOT);

/* Classify each table for tenant readiness. */
function tenant_col(array $columns, array $hints): ?string {
    foreach ($hints as $h) {
        if (in_array($h, $columns, true)) return $h;
    }
    return null;
}

$schema = [
    'has_tenant_col'   => [], // already scoped
    'needs_tenant_col' => [], // business tables, no tenant col
    'global'           => [], // platform-level, expected to be unscoped
];
foreach ($tables as $name => $info) {
    $tc = tenant_col($info['columns'], $TENANT_COL_HINTS);
    if ($tc !== null) {
        $schema['has_tenant_col'][$name] = $tc;
    } elseif (in_array($name, $GLOBAL_TABLES, true)
              || str_ends_with($name, '_backup')
              || str_starts_with($name, 'subscription')) {
        $schema['global'][] = $name;
    } else {
        $schema['needs_tenant_col'][] = $name;
    }
}

/* Schema drift: tables that exist in a dump but have no migration file creating them. */
$migrationTables = [];
$dumpTables = [];
foreach ($tables as $name => $info) {
    $inMigration = false; $inDump = false;
    foreach ($info['files'] as $rf) {
        if (stripos($rf, 'migration') !== false) $inMigration = true;
        else $inDump = true;
    }
    if ($inMigration) $migrationTables[] = $name;
    if ($inDump) $dumpTables[] = $name;
}
$driftNoMigration = array_values(array_diff($dumpTables, $migrationTables));

/* Detect competing/duplicate subsystems by name family. */
$families = [
    'cart'  => ['cart_items','cart_sessions','store_cart','store_saved_for_later','saved_for_later'],
    'order' => ['store_orders','store_order_items','orders','order_items'],
    'product' => ['products','store_products'],
    'category' => ['product_categories','store_categories'],
];
$duplicates = [];
foreach ($families as $fam => $cands) {
    $present = array_values(array_intersect($cands, array_keys($tables)));
    if (count($present) > 1) $duplicates[$fam] = $present;
}

/* --------------------------------------------- code pattern analysis */

$patterns = [
    'pdo_prepare'        => 0,  // ->prepare(
    'pdo_query_concat'   => 0,  // ->query( with concatenation / interpolation
    'raw_input_in_sql'   => [], // files where $_GET/$_POST appear near SQL keywords
    'base_model'         => [], // files defining an abstract/base model
    'session_usage'      => 0,  // $_SESSION references
    'mysqli_usage'       => 0,
    'tenant_aware_query' => 0,  // queries already mentioning a tenant column
];

$sqlKeyword = '/\b(SELECT|INSERT|UPDATE|DELETE)\b/i';

foreach ($codeFiles as $f) {
    $relf = rel($f, $ROOT);
    if (stripos($relf, 'vendor/') === 0) continue;
    $size = @filesize($f);
    if ($size === false || $size > 2_000_000) continue;
    $src = (string)@file_get_contents($f);
    if ($src === '') continue;

    $patterns['pdo_prepare']      += preg_match_all('/->\s*prepare\s*\(/i', $src);
    $patterns['session_usage']    += preg_match_all('/\$_SESSION\b/', $src);
    $patterns['mysqli_usage']     += preg_match_all('/\bmysqli\b/i', $src);
    $patterns['tenant_aware_query'] += preg_match_all('/tenant_id|store_id|branch_id/i', $src);

    // ->query( ... ) whose argument contains a PHP variable = not a static query.
    // A safe static query has no '$' before the first ')'. Worth manual review.
    if (preg_match_all('/->\s*query\s*\(\s*[^)]*\$[A-Za-z_]/i', $src, $qm)) {
        $patterns['pdo_query_concat'] += count($qm[0]);
    }

    // raw superglobal in the same line as a SQL keyword
    foreach (preg_split('/\r?\n/', $src) as $line) {
        if (preg_match('/\$_(GET|POST|REQUEST)\b/', $line) && preg_match($sqlKeyword, $line)) {
            $patterns['raw_input_in_sql'][$relf] = ($patterns['raw_input_in_sql'][$relf] ?? 0) + 1;
        }
    }

    // base/abstract model
    if (preg_match('/abstract\s+class\s+\w*Model/i', $src)
        || preg_match('/class\s+(Base|Abstract)Model/i', $src)) {
        $patterns['base_model'][] = $relf;
    }
}

/* --------------------------------------------- feature / reusability map */

$featureMap = [
    'Auth & users'        => ['AuthController','UserController','UserModel','Session','middleware','login','register','signup'],
    'Products (legacy)'   => ['ProductController','ProductModel'],
    'Store / e-commerce'  => ['StoreController','StoreCartController','StoreProductModel','StoreCategoryModel','StoreCartModel','checkout'],
    'Cart'                => ['CartController','CartModel'],
    'Orders'              => ['OrderModel','orders'],
    'M-Pesa / payments'   => ['MpesaService','mpesa'],
    'Subscriptions'       => ['subscription','Subscription'],
    'Blog'                => ['BlogController','BlogModel'],
    'Gallery'             => ['GalleryController','GalleryModel'],
    'Projects'            => ['ProjectController','ProjectModel'],
    'Services'            => ['ServiceController','ServiceModel'],
    'Enquiries'           => ['EnquiryController','EnquiryModel'],
    'Testimonials'        => ['TestimonialController','TestimonialModel'],
    'Mail'                => ['Mailer','EmailTemplate','OrderNotifier'],
    'Settings'            => ['SettingModel','settings'],
];
$featureHits = [];
foreach ($featureMap as $feature => $needles) {
    $hits = [];
    foreach ($codeFiles as $f) {
        $relf = rel($f, $ROOT);
        if (stripos($relf, 'vendor/') === 0) continue;
        foreach ($needles as $n) {
            if (stripos($relf, $n) !== false) { $hits[] = $relf; break; }
        }
    }
    if ($hits) $featureHits[$feature] = array_values(array_unique($hits));
}

/* --------------------------------------------- readiness score (heuristic) */

$bizTables = count($schema['has_tenant_col']) + count($schema['needs_tenant_col']);
$scopedRatio = $bizTables ? count($schema['has_tenant_col']) / $bizTables : 0;
$hasBaseModel = !empty($patterns['base_model']);
$injectionRisk = count($patterns['raw_input_in_sql']) + $patterns['pdo_query_concat'];

$score = 0;
$score += round($scopedRatio * 40);                         // tenant coverage (0-40)
$score += $hasBaseModel ? 20 : 0;                           // retrofit leverage
$score += $patterns['pdo_prepare'] > 0 ? 15 : 0;            // uses prepared stmts at all
$score += $injectionRisk === 0 ? 15 : max(0, 15 - $injectionRisk); // safety
$score += empty($duplicates) ? 10 : 3;                      // clean subsystems
$score = min(100, $score);

/* ------------------------------------------------------- build report data */

$report = [
    'generated_at' => date('Y-m-d H:i:s'),
    'root'         => $ROOT,
    'totals'       => [
        'files_scanned'   => count($allFiles),
        'php_files'       => count($codeFiles),
        'php_loc'         => $totalLoc,
        'sql_files'       => count($sqlFiles),
        'by_extension'    => $byExt,
    ],
    'dependencies' => $dependencies,
    'schema'       => [
        'tables_total'      => count($tables),
        'already_scoped'    => $schema['has_tenant_col'],
        'needs_tenant_col'  => $schema['needs_tenant_col'],
        'global_tables'     => $schema['global'],
        'schema_drift_no_migration' => $driftNoMigration,
        'duplicate_subsystems'      => $duplicates,
        'table_columns'     => array_map(fn($t) => $t['columns'], $tables),
    ],
    'patterns'     => $patterns,
    'features'     => $featureHits,
    'readiness_score' => $score,
];

/* ------------------------------------------------------------- write JSON */

$jsonOut = $ROOT . '/saas_readiness_report.json';
@file_put_contents($jsonOut, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

/* ------------------------------------------------------------- write Markdown */

function mdlist(array $a): string {
    if (!$a) return "_none_\n";
    return implode('', array_map(fn($x) => "- $x\n", $a));
}

$md  = "# SaaS / Multi-Tenant Readiness Report\n\n";
$md .= "_Generated: {$report['generated_at']}_  \n";
$md .= "_Root: `{$report['root']}`_\n\n";
$md .= "## Readiness score: {$score}/100\n";
$md .= "> Heuristic. Higher = less retrofit work. Breakdown below.\n\n";

$md .= "## 1. Code at a glance\n";
$md .= "- PHP files (excl. vendor): **{$report['totals']['php_files']}**\n";
$md .= "- Lines of PHP (your code): **{$report['totals']['php_loc']}**\n";
$md .= "- SQL files: **{$report['totals']['sql_files']}**\n";
$md .= "- Total files scanned: {$report['totals']['files_scanned']}\n\n";

$md .= "## 2. Real dependencies (from composer.json)\n";
if ($dependencies['found']) {
    $reqs = $dependencies['require'] ?: [];
    if ($reqs) {
        foreach ($reqs as $k => $v) $md .= "- `$k`: $v\n";
    } else { $md .= "_No runtime requirements declared._\n"; }
    if ($dependencies['require-dev']) {
        $md .= "\n_dev:_\n";
        foreach ($dependencies['require-dev'] as $k => $v) $md .= "- `$k`: $v\n";
    }
} else {
    $md .= "_No composer.json found at project root._\n";
}
$md .= "\n> Reality check: ignore any earlier claim of Laravel/Symfony/etc. unless it appears above.\n\n";

$md .= "## 3. Database — tenant readiness\n";
$md .= "Tables total: **" . count($tables) . "**\n\n";
$md .= "### Already tenant/store-scoped (" . count($schema['has_tenant_col']) . ")\n";
if ($schema['has_tenant_col']) {
    foreach ($schema['has_tenant_col'] as $t => $col) $md .= "- `$t` (via `$col`)\n";
} else { $md .= "_none — nothing is scoped yet_\n"; }
$md .= "\n### Business tables MISSING a tenant column (" . count($schema['needs_tenant_col']) . ")\n";
$md .= "These need a `tenant_id` added + backfill + an enforced query scope:\n";
$md .= mdlist($schema['needs_tenant_col']);
$md .= "\n### Platform-global tables (expected unscoped)\n";
$md .= mdlist($schema['global']);

$md .= "\n## 4. Schema drift & duplication\n";
$md .= "### Tables in a dump with NO migration that creates them\n";
$md .= mdlist($driftNoMigration);
$md .= "### Competing/duplicate subsystems\n";
if ($duplicates) {
    foreach ($duplicates as $fam => $list) $md .= "- **$fam**: " . implode(', ', array_map(fn($x)=>"`$x`",$list)) . "\n";
    $md .= "\n> Decide on ONE before scoping — don't tenant-ize both copies.\n";
} else { $md .= "_none detected_\n"; }

$md .= "\n## 5. Query & security patterns\n";
$md .= "- `->prepare()` calls (prepared statements): **{$patterns['pdo_prepare']}**\n";
$md .= "- `->query()` with variable concatenation (injection smell): **{$patterns['pdo_query_concat']}**\n";
$md .= "- mysqli references: **{$patterns['mysqli_usage']}**\n";
$md .= "- `\$_SESSION` references: **{$patterns['session_usage']}**\n";
$md .= "- Base/abstract Model class: " . ($hasBaseModel ? "**yes** (" . implode(', ', $patterns['base_model']) . ")" : "**no — this raises retrofit cost**") . "\n";
$md .= "- Files with raw \$_GET/\$_POST on the same line as SQL (review these):\n";
if ($patterns['raw_input_in_sql']) {
    foreach ($patterns['raw_input_in_sql'] as $f => $n) $md .= "  - `$f` ($n)\n";
} else { $md .= "  _none flagged_\n"; }

$md .= "\n## 6. Feature inventory (reusable building blocks)\n";
foreach ($featureHits as $feature => $files) {
    $md .= "- **$feature** — " . count($files) . " file(s)\n";
}

$md .= "\n## 7. Share these next\n";
$md .= "To design the tenant foundation, paste me these (real code, not this report):\n";
$md .= "1. `databases/ismano_db.sql` (source-of-truth schema)\n";
$md .= "2. `app/init.php`, `app/bootstrap.php`, `app/config/database.php` (boot + DB connection — where scoping is injected)\n";
$md .= "3. `app/controllers/AuthController.php`, `app/models/UserModel.php`, `app/models/Session.php`, `app/helpers/middleware.php`\n";
$md .= "4. One model+controller pair, e.g. `app/models/StoreProductModel.php` + `app/controllers/StoreController.php` (to see the query pattern)\n";
$md .= "5. `app/services/MpesaService.php` + `app/config/mpesa.php` (for billing)\n";

$mdOut = $ROOT . '/saas_readiness_report.md';
@file_put_contents($mdOut, $md);

/* ------------------------------------------------------------- console summary */

$line = str_repeat('=', 60);
echo "$line\n SaaS Readiness Analyzer\n$line\n";
echo "Root            : $ROOT\n";
echo "PHP files       : " . count($codeFiles) . "  (" . number_format($totalLoc) . " LOC)\n";
echo "SQL tables found: " . count($tables) . "\n";
echo "Already scoped  : " . count($schema['has_tenant_col']) . "\n";
echo "Need tenant_id  : " . count($schema['needs_tenant_col']) . "\n";
echo "Schema drift    : " . count($driftNoMigration) . " table(s) with no migration\n";
echo "Duplicate subsys: " . (count($duplicates) ?: 0) . "\n";
echo "Injection smells: " . $injectionRisk . "\n";
echo "Base model      : " . ($hasBaseModel ? "yes" : "NO") . "\n";
echo "Readiness score : $score/100\n";
echo "$line\n";
echo "Wrote:\n  - " . rel($jsonOut, $ROOT) . "\n  - " . rel($mdOut, $ROOT) . "\n";
echo "Open the .md file and paste it back to continue.\n";