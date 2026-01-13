<?php
$GRAFANA_URL = 'https://grafana.example.com'; // your Grafana base URL
$API_TOKEN   = '****************;      // your Grafana API token

/**
 * Call Grafana API
 */
function grafanaGet($endpoint)
{
    global $GRAFANA_URL, $API_TOKEN;

    $ch = curl_init($GRAFANA_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $API_TOKEN",
            "Content-Type: application/json"
        ]
    ]);
//curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Recursively collect panels using Angular plugins or datasources
 */
function collectAngularPanelsFromPanels(array $panels, array $angularPlugins, array $angularDatasourceTypes, array &$found)
{
    foreach ($panels as $panel) {
        $panelTitle = $panel['title'] ?? '(no title)';

        // 1️⃣ Panel uses Angular plugin
        if (!empty($panel['type']) && isset($angularPlugins[$panel['type']])) {
            $found[] = $panelTitle . " (Plugin: " . $angularPlugins[$panel['type']] . ")";
           // echo "Hello world";exit;
        }
        
         // 1️⃣ Panel uses Angular plugin
        if (!empty($panel['datasource']['type']) && isset($angularPlugins[$panel['datasource']['type']])) {
            $found[] = $panelTitle . " (Data Source Plugin: " . $angularPlugins[$panel['datasource']['type']] . ")";
           // echo "Hello world";exit;
        }

        // 2️⃣ Panel uses Angular datasource
        if (!empty($panel['targets']) && is_array($panel['targets'])) {
            foreach ($panel['targets'] as $target) {
                $dsName = $target['datasource'] ?? '';
                if (!empty($dsName) && is_scalar($dsName) && isset($angularDatasourceTypes[$dsName])) {
                    $found[] = $panelTitle . " (Datasource: " . $angularDatasourceTypes[$dsName] . ")";
                }
            }
        }

        // 3️⃣ Nested panels
        if (!empty($panel['panels']) && is_array($panel['panels'])) {
            collectAngularPanelsFromPanels($panel['panels'], $angularPlugins, $angularDatasourceTypes, $found);
        }
    }
}

/**
 * Collect template variables using Angular datasources (uses query field)
 */
function collectAngularTemplateVariables(array $templating, array $angularDatasourceTypes, array &$found)
{
    if (empty($templating['list']) || !is_array($templating['list'])) {
        return;
    }

    foreach ($templating['list'] as $variable) {
        $varName = $variable['name'] ?? '(no name)';
        $dsName  = $variable['query'] ?? ''; // Use query field

        if (!empty($dsName) && is_scalar($dsName) && isset($angularDatasourceTypes[$dsName])) {
            $found[] = "Template Variable: {$varName} (Datasource: " . $angularDatasourceTypes[$dsName] . ")";
        }
    }
}

/* ===============================
   1) Detect Angular Plugins
   =============================== */
$plugins = grafanaGet('/api/plugins');
$angularPlugins = [];
foreach ($plugins as $plugin) {
    if (!empty($plugin['angularDetected']) && !empty($plugin['id']) && is_scalar($plugin['id'])) {
        $angularPlugins[(string)$plugin['id']] = $plugin['name'] ?? '(no name)';
    }
}

/* ===============================
   2) Detect Angular Datasources
   =============================== */
$datasources = grafanaGet('/api/datasources');
$angularDatasources = [];
foreach ($datasources as $ds) {
    if (!empty($ds['type']) && isset($angularPlugins[$ds['type']])) {
        $angularDatasources[] = [
            'name'   => $ds['name'],
            'plugin' => $angularPlugins[$ds['type']]
        ];
    }
}

// Mapping for quick lookup
$angularDatasourceTypes = [];
if (!empty($angularDatasources)) {
    foreach ($angularDatasources as $ds) {
        $angularDatasourceTypes[$ds['name']] = $ds['plugin'];
    }
}

/* ===============================
   3) Detect Dashboards Using Angular Plugins / Datasources
   =============================== */
$dashboards = grafanaGet('/api/search?type=dash-db');
$angularDashboards = [];

foreach ($dashboards as $dash) {
    $uid   = $dash['uid'] ?? '';
    $title = $dash['title'] ?? '(no title)';

    if (empty($uid)) continue;

    $data = grafanaGet("/api/dashboards/uid/$uid");
    $panels = $data['dashboard']['panels'] ?? [];
    
    

    $angularPanels = [];
    collectAngularPanelsFromPanels($panels, $angularPlugins, $angularDatasourceTypes, $angularPanels);
    
    /*if(strpos($title,"Angular")!==false)
    {
        print_r($panels);
        print_r($angularPlugins);
        print_r($angularPanels);
        exit;
    }*/
    
    // Check template variables
    if (!empty($data['dashboard']['templating'])) {
        collectAngularTemplateVariables($data['dashboard']['templating'], $angularDatasourceTypes, $angularPanels);
    }

    if (!empty($angularPanels)) {
        $angularDashboards[] = [
            'title'  => $title,
            'link'   => $data['meta']['url'] ?? '#',
            'panels' => $angularPanels
        ];
    }
}


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Grafana Angular Audit</title>
<style>
body { font-family: system-ui, Arial, sans-serif; background: #f4f6f8; padding: 20px; }
h1 { margin-bottom: 10px; }
section { background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
th { background: #fafafa; }
.badge { display: inline-block; padding: 4px 8px; background: #ff4d4f; color: #fff; border-radius: 4px; font-size: 12px; margin: 2px 2px 2px 0; }
.empty { color: #6b7280; font-style: italic; }
a { color: #1890ff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<h1>🚨 Grafana Angular Audit</h1>

<section>
<h2>1️⃣ Angular Plugins</h2>
<?php if (empty($angularPlugins)): ?>
<p class="empty">No Angular plugins detected 🎉</p>
<?php else: ?>
<table>
<tr><th>Plugin Name</th><th>Plugin ID</th></tr>
<?php foreach ($angularPlugins as $id => $name): ?>
<tr>
    <td><?= htmlspecialchars($name) ?></td>
    <td><?= htmlspecialchars($id) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>

<section>
<h2>2️⃣ Datasources Using Angular Plugins</h2>
<?php if (empty($angularDatasources)): ?>
<p class="empty">No datasources using Angular plugins</p>
<?php else: ?>
<table>
<tr><th>Datasource Name</th><th>Plugin</th></tr>
<?php foreach ($angularDatasources as $ds): ?>
<tr>
    <td><?= htmlspecialchars($ds['name']) ?></td>
    <td><?= htmlspecialchars($ds['plugin']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>

<section>
<h2>3️⃣ Dashboards Using Angular Plugins or Datasources</h2>
<?php if (empty($angularDashboards)): ?>
<p class="empty">No dashboards using Angular plugins or datasources 🎉</p>
<?php else: ?>
<table>
<tr><th>Dashboard</th><th>Panels / Template Variables Using Angular</th></tr>
<?php foreach ($angularDashboards as $row): ?>
<tr>
    <td>
        <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank">
            <?= htmlspecialchars($row['title']) ?>
        </a>
    </td>
    <td>
        <?php foreach ($row['panels'] as $panelInfo): ?>
            <div class="badge"><?= htmlspecialchars($panelInfo) ?></div>
        <?php endforeach; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>

</body>
</html>
