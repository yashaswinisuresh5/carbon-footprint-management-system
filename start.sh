#!/bin/bash

# start.sh - Multi-process startup script for Render deployment
set -e

echo "=================================================="
echo "🚀 EcoTrace Service Orchestrator Starting Up..."
echo "=================================================="

# Start MariaDB service
echo "🔧 Booting MariaDB Server..."
service mariadb start

# Wait for MariaDB port to become active and responsive
echo "⏳ Verifying database socket connection..."
until mariadb-admin ping -h"localhost" --silent; do
    echo "   -> Waiting for MariaDB socket to open..."
    sleep 1.5
done
echo "✅ MariaDB is active and healthy!"

# Ensure the root user can connect via standard passwordless localhost TCP/IP connection
# This perfectly aligns with our PHP config/db.php definitions (localhost / root / blank password)
echo "🔑 Applying local database privileges..."
mariadb -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;"
echo "✅ Database access keys synced successfully!"

echo "=================================================="
echo "🌐 Starting Apache HTTP Daemon..."
echo "=================================================="
# Start Apache web daemon in the foreground to keep the container running
exec apache2-foreground
