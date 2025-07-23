# Simple Uptime Monitoring

## Uptime Kita

Kita is the Indonesian word that means "Us"; this means that the uptime can be used for all of us

## 🥔 Live Demo

U can try it [here](https://uptime.syofyanzuhad.dev)

## ⭐ Features

- Monitoring uptime for HTTP(s)
- Fancy, Reactive, Fast UI/UX
- Notifications via Telegram, Slack, Email (SMTP), and the others are still in progress
- Multiple status pages
- Certificate check

## 🔧 How to Install

### Requirements:
- php ^8.2 (and meet [Laravel 12.x requirements](https://laravel.com/docs/12.x/deployment#server-requirements)).
- Node.js ^22
- Redis
- SQLite
- Crontab
- Supervisord

### Installation:
- Clone repository:
```bash
git clone https://github.com/syofyanzuhad/uptime-kita
```
- Install PHP dependencies: 
```bash
composer install
```
- Install javscript dependencies and build assets:
```bash
# install dependencies
npm install

# build assetes
npm run build
```
- Setup .env file
```bash
# change directory to the uptime-kita
cd uptime-kita

# copy .env file from .env.example
cp .env.example .env
```
- Generate application key: 
```bash
php artisan key:generate
```
- Run migration and seeder (this will prompt to create database.sqlite file if it doesn't exists)
```bash
php artisan migrate --seed --force
```
- Run the scheduler using cron job:
```bash
# open cron configuration
crontab -e
```
```bash
# copy this text to the end of line 
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
> [!NOTE]
> change the path to your project path

Read more about [laravel scheduler](https://laravel.com/docs/12.x/scheduling#running-the-scheduler)
- Run the background job using supervisord:
```bash
# install supervisord
sudo apt-get install supervisor

# add new file on /etc/supervisor/conf.d directory
touch /etc/supervisor/conf.d/horizon.conf
```
```bash
# copy this text
[program:horizon]
process_name=%(program_name)s
command=php /home/path-to-project/artisan horizon
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/path-to-project/horizon.log
stopwaitsecs=3600
```
> [!NOTE]
> change the path to your project path
```bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start horizon
```

Read more about [laravel horizon](https://laravel.com/docs/12.x/horizon#installing-supervisor)

## Roadmap

- [x] Uptime monitoring
- [x] SSL Monitoring
- [x] Monitoring history
- [ ] Notification:
  - [x] Email
  - [x] Telegram
  - [ ] Slack
  - [ ] Discord
- [x] Status page
- [ ] Do you have any suggestions?

## Screenshots

### Private monitors
<img width="2048" height="1844" alt="uptime syofyanzuhad dev_dashboard (1)" src="https://github.com/user-attachments/assets/812d9b8a-869d-4f7d-9e54-828c1e41a27c" />

### Public monitors
<img width="2048" height="1844" alt="uptime syofyanzuhad dev_dashboard (2)" src="https://github.com/user-attachments/assets/f591c415-01d0-430d-b0b6-6d11fb57c027" />

### Notification
<img width="2048" height="1844" alt="uptime syofyanzuhad dev_status-pages_1 (1)" src="https://github.com/user-attachments/assets/f1ebd743-3003-46e0-aba2-5bb6713084cf" />

<img width="583" height="893" alt="Screenshot 2025-07-22 at 09 18 08" src="https://github.com/user-attachments/assets/ddfb62da-bacb-4a5e-ba8f-c0005114bd08" />

### Status Page
<img width="2048" height="1844" alt="uptime syofyanzuhad dev_status-pages_1" src="https://github.com/user-attachments/assets/8102d0bc-978f-4888-8503-d8e6a2923130" />

<img width="2048" height="1844" alt="uptime syofyanzuhad dev_status_syofyan-zuhad" src="https://github.com/user-attachments/assets/3059f9f1-c98d-4c8b-a6e2-3e405021523f" />
