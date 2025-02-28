Server Metrics Plugin Installation Manual

## 1. WordPress Plugin Installation

Download plugin here: https://github.com/netzencatura/server-metrics/releases
Install the Server Metrics plugin on your WordPress site (preferably on the Enhance Control Panel master server, but any WordPress installation will work)
Activate the plugin
Go to "Server Metrics" settings in WordPress admin
Copy the API token that was automatically generated

## 2. Vector Installation

On each web server you want to monitor:

```bash
curl -L https://github.com/netzencatura/server-metrics/raw/refs/heads/main/install-vector.sh | bash
```

This will install Vector with the initial configuration.

## 3. Vector Configuration

On each web server, you need to modify two configuration files:

a) Configure API endpoint and token

Edit file: /etc/vector/sinks/container_metrics_file.yaml

```bash
type: http
inputs: ["filter_container_metrics"]
method: post
uri: https://your-wordpress-site.com/wp-json/server-metrics/v1/collect/
encoding:
 codec: json
 except_fields: ["cpu_usage", "domain", "io_read_rate", "io_write_rate", "mem_usage", "uuid"]
request:
 headers:
 content-type: application/json
 X-API-Key: "your-api-token-from-plugin-settings"
```

b) Configure server name

Edit file: /etc/vector/transforms/parse_container_metrics.yaml

```bash
type: remap
inputs: ["container_metrics"]
source: |
 . = parse_regex!(string!(.message), r'^uuid=(?P[0-9a-f-]+)\s+domain=(?P[^\s]+)\s+cpu_usage=(?P[0-9]+)\s+mem_usage=(?P[0-9.]+)\s+io_read_rate=(?P[0-9]+)\s+io_write_rate=(?P[0-9]+)$')
 .container_metrics = {
 "uuid": .uuid,
 "domain": .domain,
 "cpu_usage": to_int!(.cpu_usage),
 "mem_usage": to_float!(.mem_usage),
 "io_read_rate": to_int!(.io_read_rate),
 "io_write_rate": to_int!(.io_write_rate),
 "timestamp": now(),
 "type": "usage",
 "server": "your-server-hostname"
 }
```

Note: Replace "your-server-hostname" with your actual server hostname as detected in Enhance CP

## 4. Restart Vector

After making the changes, restart Vector:

```bash
systemctl restart vector
```

## 5. Verify Installation

Check Vector verify:

```bash
/root/.vector/bin/vector validate --config-dir /etc/vector
```

Go to your WordPress admin panel > Server Metrics
You should see separate tables for each server showing container metrics

## 6. Vector Service Management

After making configuration changes, you'll need to restart Vector. Here are the common service commands:

# Restart Vector service

```bash
systemctl restart vector
```

# Stop Vector service

```bash
systemctl stop vector
```

# Start Vector service

```bash
systemctl start vector
```

# Verify Vector configuration

```bash
/root/.vector/bin/vector validate --config-dir /etc/vector
```

## 7. Notes

Go to your WordPress admin panel > Server Metrics
You should see separate tables for each server showing container metrics
Notes
Each server sending data to the same WordPress installation must use the same API token
Server names should match their Enhance CP hostnames for consistency
The plugin works best when installed on the master server, but can work on any WordPress installation that's accessible via HTTP
