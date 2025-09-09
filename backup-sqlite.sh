#!/bin/bash

# SQLite Backup Script for Production
# Place this in your project root and add to crontab

DB_PATH="database/database.sqlite"
BACKUP_DIR="storage/backups/database"
BACKUP_RETENTION_DAYS=7

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Create timestamped backup
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.sqlite"

# Perform backup using SQLite's backup command
sqlite3 "$DB_PATH" ".backup '$BACKUP_FILE'"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup successful: $BACKUP_FILE"
    
    # Compress the backup
    gzip "$BACKUP_FILE"
    echo "[$(date)] Backup compressed: ${BACKUP_FILE}.gz"
    
    # Remove old backups
    find "$BACKUP_DIR" -name "backup_*.sqlite.gz" -mtime +$BACKUP_RETENTION_DAYS -delete
    echo "[$(date)] Old backups cleaned up (older than $BACKUP_RETENTION_DAYS days)"
else
    echo "[$(date)] Backup failed!" >&2
    exit 1
fi