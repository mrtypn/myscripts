# Grafana Angular Audit

A lightweight PHP web script that scans a Grafana instance and identifies **Angular-based usage** across **plugins, datasources, dashboards, panels, and template variables**.

This tool is especially useful when preparing for **Grafana Angular deprecation**, upgrades, or plugin migration.

---

## ✨ Features

This script detects:

- ✅ Angular plugins
- ✅ Datasources using Angular plugins
- ✅ Dashboards using Angular plugins
- ✅ Panels using Angular plugins
- ✅ Panels using Angular datasources
- ✅ Template variables using Angular datasources (`templating.list[].query`)
- ✅ Dashboard links (using Grafana `url`)
- ✅ Panel titles and template variable names
- ✅ Clean, browser-friendly HTML report

Each dashboard appears **only once**, even if multiple Angular panels or variables exist.

---

## 🧰 Requirements

- PHP **7.2+**
- PHP extensions:
  - `curl`
  - `json`
- Grafana access with:
  - API enabled
  - Permission to read dashboards, plugins, and datasources

---

## 🔐 Grafana API Token

You must create a **Grafana API token** with **Viewer** or higher permissions.

### Create Token

1. Go to **Grafana → Administration → API Keys**
2. Click **New API Key**
3. Role: **Viewer** (Admin also works)
4. Copy the generated token

---

## ⚙️ Configuration

Edit the top of `grafana-angular-scan.php`:

```php
$GRAFANA_URL = 'https://grafana.example.com';
$API_TOKEN   = 'YOUR_GRAFANA_API_TOKEN';
```

⚠️ Do **not** include a trailing slash in the URL.

---

## ▶️ Usage

### Option 1: Run via Web Browser

Place the script in your web root:

```text
/var/www/html/grafana-angular-scan.php
```

Open in browser:

```text
https://your-server/grafana-angular-scan.php
```

---

### Option 2: Run via CLI (HTML Output)

```bash
php grafana-angular-scan.php > report.html
```

Then open `report.html` in a browser.

---

## 📊 Output Overview

### 1️⃣ Angular Plugins

Lists all installed Grafana plugins where:

```text
angularDetected = true
```

---

### 2️⃣ Angular Datasources

Lists datasources backed by Angular plugins.

---

### 3️⃣ Dashboards Using Angular

For each affected dashboard, the report shows:

- Dashboard name (clickable link)
- Panels using:
  - Angular plugins
  - Angular datasources
- Template variables using Angular datasources

Example:

```text
Network Overview
  - Latency Map (Plugin: Worldmap)
  - CPU Usage (Datasource: Simple JSON)
  - Template Variable: datasource (Datasource: Simple JSON)
```

---

## 🛡️ Safety & Stability

- Handles nested panels (rows and panel groups)
- Guards against nulls, arrays, and malformed API responses
- Avoids duplicate dashboards
- Read-only API access
- No writes or modifications to Grafana

Safe to run in production environments.

---

## 🧪 Debugging (Optional)

To enable curl debug output inside `grafanaGet()`:

```php
curl_setopt($ch, CURLOPT_VERBOSE, true);
```

To capture curl debug output programmatically:

```php
CURLOPT_STDERR => fopen('php://temp', 'w+')
```

---

## 🎯 Common Use Cases

- Grafana Angular deprecation readiness
- Plugin modernization planning
- Upgrade impact analysis
- Compliance / audit reporting
- Identifying dashboards requiring refactoring

---

## 🚀 Future Enhancements (Ideas)

- Export results to CSV / JSON
- Highlight deprecated plugins
- Filter dashboards by folder or tag
- Grafana version compatibility check
- CI/CD validation step

---

## 🧑‍💻 Maintainer Notes

Script name:

```text
grafana-angular-scan.php
```

Suggested location:

```text
/tools/grafana/grafana-angular-scan.php
```

---

## 📜 License

Internal / Private use  
No warranty expressed or implied.
