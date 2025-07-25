# Simple Uptime Monitoring

## 🟩 Uptime Kita

<p align='center'>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
	  <img src='https://img.shields.io/endpoint?url=https%3A%2F%2Fhits.dwyl.com%2Fsyofyanzuhad%2Fuptime-kita.json%3Fcolor%3Dgreen'>
	</a>
  <a target="_blank" href="https://github.com/syofyanzuhad/uptime-kita">
    <img src="https://img.shields.io/github/last-commit/syofyanzuhad/uptime-kita" />
  </a>
	<a href="https://github.com/syofyanzuhad/uptime-kita/issues">
    <img alt="GitHub Issues or Pull Requests" src="https://img.shields.io/github/issues/syofyanzuhad/uptime-kita">
  </a>
	<a href="https://github.com/syofyanzuhad/uptime-kita/pulls">
		<img src="https://img.shields.io/github/issues-pr/syofyanzuhad/uptime-kita" alt="Pull Requests Badge"/>
	</a>
  <a href="https://github.com/syofyanzuhad/uptime-kita/graphs/contributors">
		<img alt="GitHub contributors" src="https://img.shields.io/github/contributors/syofyanzuhad/uptime-kita?color=2b9348">
	</a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
		<img src='https://img.shields.io/github/forks/syofyanzuhad/uptime-kita'>
	</a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
    <img src="https://github.com/syofyanzuhad/uptime-kita/actions/workflows/tests.yml/badge.svg" />
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
		<img src='https://img.shields.io/github/stars/syofyanzuhad/uptime-kita'>
	</a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
    <img src="https://visitor-badge.laobi.icu/badge?page_id=syofyanzuhad.uptime-kita" />
  </a>
</p>

<img width="2048" height="1844" alt="uptime syofyanzuhad dev_status_syofyan-zuhad" src="https://github.com/user-attachments/assets/3059f9f1-c98d-4c8b-a6e2-3e405021523f" />

Kita is the Indonesian word that means "Us"; this means that the uptime can be used for all of us

## 🥔 Live Demo

U can try the [uptime kita demo](https://uptime.syofyanzuhad.dev) (Server located on Germany)

## ⭐ Features

- Google Oauth authentication
- Monitoring uptime for HTTP(s)
- Certificate check
- Fancy, Reactive, Fast UI/UX
- Notifications via Telegram, Slack, Email (SMTP), and the others are still in progress
- Multiple status pages

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
    ```bash
    # admin credential
    ADMIN_EMAIL=admin@syofyanzuhad.dev
    ADMIN_PASSWORD=password123
    
    # google oauth https://developers.google.com/identity/protocols/oauth2?hl=id
    GOOGLE_CLIENT_ID=
    GOOGLE_CLIENT_SECRET=
    
    # telegram bot token https://t.me/botfather
    TELEGRAM_BOT_TOKEN=
    
    # email configuration with https://resend.com/
    RESEND_API_KEY=
    ```
- Generate application key: 
    ```bash
    php artisan key:generate
    ```
- Update default monitor on `database/seeder/monitors/` directory and `MonitorSeeder.php` file
    ```
    - seeders
      |_ monitors
      |  |_monitors.php
      |  |_collages.php
      |_ MonitorSeeer.php
    ```
    ```php
        // MonitorSeeder.php

        /**
        * Run the database seeds.
        */
        public function run(): void
        {
            $monitors = require database_path('seeders/monitors/monitors.php');
            $collages = require database_path('seeders/monitors/collage.php');

            // others code
        }
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
    # copy this text to the end of line (change the path to your project path)
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```

    > Read more about [laravel scheduler](https://laravel.com/docs/12.x/scheduling#running-the-scheduler)

- There are 2 ways to run the background job:

  1. Laravel Queue

        ```bash
        # install supervisord
        sudo apt-get install supervisor
        
        # add new file on /etc/supervisor/conf.d directory
        touch /etc/supervisor/conf.d/laravel-worker.conf
        ```
        ```bash
        # copy this text (change the path to your project path)
        [program:laravel-worker]
        process_name=%(program_name)s_%(process_num)02d
        command=php /home/path-to-project/artisan queue:work sqs --sleep=3 --tries=3 --max-time=3600
        autostart=true
        autorestart=true
        stopasgroup=true
        killasgroup=true
        user=forge
        numprocs=8
        redirect_stderr=true
        stdout_logfile=/home/forge/app.com/worker.log
        stopwaitsecs=3600
        ```

        > Read more about [laravel queue](https://laravel.com/docs/12.x/queues#supervisor-configuration)

  2. Laravel Horizon
  
        ```bash
        # install supervisord
        sudo apt-get install supervisor
        
        # add new file on /etc/supervisor/conf.d directory
        touch /etc/supervisor/conf.d/horizon.conf
        ```
        ```bash
        # copy this text (change the path to your project path)
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

        ```bash
        sudo supervisorctl reread
        
        sudo supervisorctl update
        
        sudo supervisorctl start horizon
        ```

        > Read more about [laravel horizon](https://laravel.com/docs/12.x/horizon#installing-supervisor)

> [!NOTE]
> change the path to your project path

## 🛣️ Roadmap

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

## 📸 Screenshots

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

## 📊 Activity
<a href="https://trendshift.io/repositories/634" target="_blank"><img src="https://trendshift.io/api/badge/repositories/634" alt="syofyanzuhad/uptime-kita | Trendshift" style="width: 250px; height: 55px;" width="250" height="55"/></a>

## ⭐️ Star History

<a href="https://www.star-history.com/#syofyanzuhad/uptime-kita&Date">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date&theme=dark" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date" />
   <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date" />
 </picture>
</a>

---

Uptime Kita is an open-sourced software licensed under the [Apache-2.0](https://github.com/syofyanzuhad/uptime-kita/LICENSE)
