#!/bin/bash

if [ "$EUID" -ne 0 ]; then 
  echo "Please run as root"
  exit 1
fi

VECTOR_HOME="/root/.vector"
VECTOR_BIN="${VECTOR_HOME}/bin/vector"
CONFIG_ARCHIVE="https://github.com/netzencatura/server-metrics/raw/refs/heads/main/vector-config.tar.gz"

echo "1. Removing old installation..."
systemctl stop vector 2>/dev/null
systemctl disable vector 2>/dev/null
rm -rf /etc/vector
rm -rf /root/.vector
rm -f /etc/systemd/system/vector.service
rm -f /etc/systemd/system/multi-user.target.wants/vector.service
systemctl reset-failed vector 2>/dev/null
systemctl daemon-reload

echo "2. Installing Vector..."
curl --proto '=https' --tlsv1.2 -sSfL https://sh.vector.dev | bash -s -- -y

echo "3. Creating directory structure..."
mkdir -p /etc/vector
mkdir -p /var/lib/vector

echo "4. Downloading and extracting configuration..."
cd /etc/vector
curl -sSL ${CONFIG_ARCHIVE} | tar xz --no-same-owner

echo "5. Setting permissions..."
chmod 755 "${VECTOR_BIN}"
chown -R root:root "${VECTOR_HOME}"
chown -R root:root /var/lib/vector
chown -R root:root /etc/vector
find /etc/vector -type d -exec chmod 755 {} \;
find /etc/vector -type f -exec chmod 644 {} \;
chmod 755 /var/lib/vector

echo "6. Creating systemd service..."
cat > /etc/systemd/system/vector.service << EOF
[Unit]
Description=Vector
Documentation=https://vector.dev
After=network-online.target
Requires=network-online.target

[Service]
User=root
Group=root
ExecStartPre=/bin/mkdir -p /var/lib/vector
ExecStartPre=${VECTOR_BIN} validate --config-dir /etc/vector
ExecStart=${VECTOR_BIN} --config-dir /etc/vector
Restart=always
StartLimitInterval=0
RestartSec=1

[Install]
WantedBy=multi-user.target
EOF

echo "7. Starting service..."
systemctl daemon-reload
systemctl enable vector
systemctl start vector

echo "8. Checking status..."
systemctl status vector
if [ -f "${VECTOR_BIN}" ]; then
    "${VECTOR_BIN}" --version
else
    echo "Vector is not properly installed"
fi

echo "Installation completed!"