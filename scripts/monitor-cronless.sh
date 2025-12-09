#!/bin/bash

# Monitor and auto-restart Laravel Cronless Scheduler
# Usage: ./scripts/monitor-cronless.sh

# Configuration
PROJECT_DIR="/Users/macbookpro/Herd/Uptime-Kita"
LOG_FILE="$PROJECT_DIR/storage/logs/cronless-monitor.log"
PID_FILE="$PROJECT_DIR/storage/app/cronless.pid"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

is_running() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if ps -p "$PID" > /dev/null 2>&1; then
            # Check if it's actually our artisan command
            if ps -p "$PID" -o args= | grep -q "schedule:run-cronless"; then
                return 0
            fi
        fi
        # PID file exists but process is not running, clean up
        rm -f "$PID_FILE"
    fi
    return 1
}

start_cronless() {
    log "${GREEN}Starting Laravel Cronless Scheduler...${NC}"

    cd "$PROJECT_DIR" || exit 1

    # Start the scheduler in background
    nohup php artisan schedule:run-cronless > "$PROJECT_DIR/storage/logs/scheduler.log" 2>&1 &

    # Save PID
    echo $! > "$PID_FILE"

    log "${GREEN}Scheduler started with PID: $(cat $PID_FILE)${NC}"
}

stop_cronless() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        log "${YELLOW}Stopping scheduler (PID: $PID)...${NC}"

        # Try graceful shutdown first
        kill "$PID" 2>/dev/null

        # Wait up to 10 seconds for process to stop
        for i in {1..10}; do
            if ! ps -p "$PID" > /dev/null 2>&1; then
                break
            fi
            sleep 1
        done

        # Force kill if still running
        if ps -p "$PID" > /dev/null 2>&1; then
            log "${YELLOW}Force killing process...${NC}"
            kill -9 "$PID" 2>/dev/null
        fi

        rm -f "$PID_FILE"
        log "${GREEN}Scheduler stopped${NC}"
    else
        log "${YELLOW}No PID file found${NC}"
    fi
}

check_and_restart() {
    if is_running; then
        PID=$(cat "$PID_FILE")

        # Check memory usage (in MB)
        MEMORY=$(ps -p "$PID" -o rss= | awk '{print $1/1024}')
        MEMORY_INT=$(printf "%.0f" "$MEMORY")

        log "Scheduler is running (PID: $PID, Memory: ${MEMORY_INT}MB)"

        # Restart if memory usage is too high (> 500MB)
        if [ "$MEMORY_INT" -gt 500 ]; then
            log "${RED}Memory usage too high (${MEMORY_INT}MB), restarting...${NC}"
            stop_cronless
            sleep 2
            start_cronless
        fi
    else
        log "${RED}Scheduler is not running, starting...${NC}"
        start_cronless
    fi
}

# Main script
case "${1:-check}" in
    start)
        if is_running; then
            log "${YELLOW}Scheduler is already running (PID: $(cat $PID_FILE))${NC}"
        else
            start_cronless
        fi
        ;;
    stop)
        stop_cronless
        ;;
    restart)
        stop_cronless
        sleep 2
        start_cronless
        ;;
    status)
        if is_running; then
            PID=$(cat "$PID_FILE")
            MEMORY=$(ps -p "$PID" -o rss= | awk '{print $1/1024}')
            CPU=$(ps -p "$PID" -o %cpu= | awk '{print $1}')
            UPTIME=$(ps -p "$PID" -o etime= | xargs)

            echo -e "${GREEN}Scheduler is running${NC}"
            echo "PID: $PID"
            echo "Memory: $(printf "%.2f" "$MEMORY")MB"
            echo "CPU: ${CPU}%"
            echo "Uptime: $UPTIME"
        else
            echo -e "${RED}Scheduler is not running${NC}"
        fi
        ;;
    check)
        check_and_restart
        ;;
    monitor)
        log "Starting continuous monitoring (check every 60 seconds)..."
        while true; do
            check_and_restart
            sleep 60
        done
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|check|monitor}"
        echo ""
        echo "Commands:"
        echo "  start   - Start the scheduler"
        echo "  stop    - Stop the scheduler"
        echo "  restart - Restart the scheduler"
        echo "  status  - Show scheduler status"
        echo "  check   - Check and restart if needed (one-time)"
        echo "  monitor - Continuous monitoring (runs forever)"
        exit 1
        ;;
esac
