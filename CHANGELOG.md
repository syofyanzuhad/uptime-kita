# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [0.3.0](https://github.com/syofyanzuhad/uptime-kita/compare/v0.2.0...v0.3.0) (2026-04-23)


### Features

* add cloudflare web analytics script ([3f07018](https://github.com/syofyanzuhad/uptime-kita/commit/3f07018b70c2ba6c30c924c9ebca998373721154))
* add website carbon badge to public pages ([d04c948](https://github.com/syofyanzuhad/uptime-kita/commit/d04c948e519a5e25cfacca5a834298b02d55f52d))
* limit uptime history to 90 days and add visual fallback for today's uptime ([82fe0ef](https://github.com/syofyanzuhad/uptime-kita/commit/82fe0efe3bf0c6e0da4fc0fc2018036049e4d9f9))


### Bug Fixes

* ensure daily uptime calculations use full previous day ([7c8259f](https://github.com/syofyanzuhad/uptime-kita/commit/7c8259fde32a44b7e254be8d45f7178ea62b7834))
* prevent redundant monitor history records ([32e6954](https://github.com/syofyanzuhad/uptime-kita/commit/32e69546b97c3f074f5a0d0aeb36cc5bcba863ec))
* reset default user agent to bypass strict WAFs like Akamai ([b58e840](https://github.com/syofyanzuhad/uptime-kita/commit/b58e8404cfb036dc3378ddb133af71309cae2ec6))
* resolve 500 error in public status page monitors API ([bcaaa6f](https://github.com/syofyanzuhad/uptime-kita/commit/bcaaa6f4d31da197750d886304f9dbb78b177154))
* resolve duplicate history records in public view ([7e323ef](https://github.com/syofyanzuhad/uptime-kita/commit/7e323ef8ccccb4d1847e04616d80f94b8095bb11))
* show correct SSL status in monitor compact modal ([6950753](https://github.com/syofyanzuhad/uptime-kita/commit/695075301e9ddc4f9667969747566d86878c6bcb))


### Performance

* ensure unique monitor history records per minute ([5c83d00](https://github.com/syofyanzuhad/uptime-kita/commit/5c83d00c218cc97640726886fe420eb98246f3ca))
* fix N+1 queries in batched notification system ([420ef91](https://github.com/syofyanzuhad/uptime-kita/commit/420ef914d1e1deeb99dc7a028df02ec83ea42ff2))
* optimize public monitor pages and counts to prevent timeouts ([1ef1783](https://github.com/syofyanzuhad/uptime-kita/commit/1ef1783d608b210e06cb04a4e3c160177e22b679))
* optimize public status page monitors API ([eccb21d](https://github.com/syofyanzuhad/uptime-kita/commit/eccb21dabcc1972c071572b58d4f665ab2aad79f))
* optimize status page listing with monitor counts ([d437968](https://github.com/syofyanzuhad/uptime-kita/commit/d43796875f3ac48db5d8466c2586c82bb5e68a1d))


### Tests

* add history api and limit verification tests ([5808d79](https://github.com/syofyanzuhad/uptime-kita/commit/5808d79ef86ba8655a6c6c61d02949e02bc2d56b))
* add verification for daily uptime calculation and backfill ([86a7c90](https://github.com/syofyanzuhad/uptime-kita/commit/86a7c90a9df0ae7f460a55a53542e4b013d096d6))
* fix cleanup command tests to handle new model hooks ([54fd7ea](https://github.com/syofyanzuhad/uptime-kita/commit/54fd7eaed1a8806db6a6d738cfa3f1f1b278b07f))

## [0.2.0](https://github.com/syofyanzuhad/uptime-kita/compare/v0.1.2...v0.2.0) (2026-04-22)


### Features

* add browser notifications setting UI ([8bc4c17](https://github.com/syofyanzuhad/uptime-kita/commit/8bc4c1731943c3cf3480fc2211c5a3efb499ec3d))
* add clickable link to monitor URL in detail modal and import dropdown components for compact view ([cf7e197](https://github.com/syofyanzuhad/uptime-kita/commit/cf7e19758320ad7c72eda70486164bbc9bcadd66))
* add export and import functionality for monitors ([c51569c](https://github.com/syofyanzuhad/uptime-kita/commit/c51569ca353feb496819e88554e4096d715606cb))
* add interval and ssl check status to monitors index and detail view ([af339cc](https://github.com/syofyanzuhad/uptime-kita/commit/af339cc5a688c315c99458d4a4302ef46a03d3cc))
* add support for per-monitor and global proxies in SmartRetryService ([1dcfe75](https://github.com/syofyanzuhad/uptime-kita/commit/1dcfe757c42eebd4b617147328fcc1efeb12e075))
* **compact:** display 24h statistics across all wallboard views ([d6af2a7](https://github.com/syofyanzuhad/uptime-kita/commit/d6af2a7369fc6396dd9f6714b73f670fbc6e6e0c))
* **compact:** implement server-side sorting for massive monitor datasets ([2542313](https://github.com/syofyanzuhad/uptime-kita/commit/2542313e6f21a885345c5fc8793dff4c8708f7e5))
* **compact:** use today_uptime_percentage instead of uptime_24h and enable nightwatch:agent ([373b4ce](https://github.com/syofyanzuhad/uptime-kita/commit/373b4ced553fadd3bc6799b831369f080f9a6507))
* implement desktop notification logic ([30598bd](https://github.com/syofyanzuhad/uptime-kita/commit/30598bd0026782a5053b3722ee7dcc7252c2d5c7))
* **monitor:** implement detail, edit and create features using modals ([792549e](https://github.com/syofyanzuhad/uptime-kita/commit/792549e588d072f9f0626c75ad5c0b9d87957383))
* **monitor:** implement public Status Wallboard with compact view switcher ([48465b2](https://github.com/syofyanzuhad/uptime-kita/commit/48465b23570a129b69adbb9ea0bad7f7065c74e0))
* **nav:** add Compact View link to main sidebar ([ba2fa74](https://github.com/syofyanzuhad/uptime-kita/commit/ba2fa742dc1e7729b83a98877059bbd0a707515b))
* re-install laravel/nightwatch and restore agent configuration ([031231a](https://github.com/syofyanzuhad/uptime-kita/commit/031231a33a1da04109baad5b611987aa95ce334c))
* **ui:** add compact view partials (dots, table, bars, cards) ([a99f070](https://github.com/syofyanzuhad/uptime-kita/commit/a99f0700ded6645bcce73a758f6de6cb592d332e))
* **ui:** add WallboardLayout for full-screen NOC/TV views ([4561218](https://github.com/syofyanzuhad/uptime-kita/commit/4561218de2d7bf17cbc2adcf682360f9c459ff3e))


### Bug Fixes

* **compact:** fix invalid end tag in Compact.vue template ([d858c94](https://github.com/syofyanzuhad/uptime-kita/commit/d858c947fb74c3d3b2e5c30dc50f65dfbe22b968))
* **compact:** handle null statistics gracefully in wallboard views ([82ad42e](https://github.com/syofyanzuhad/uptime-kita/commit/82ad42e74b7cf59c6d55058313a649fcb04c68a3))
* **compact:** implement pagination and server-side search to handle 54k+ monitors ([a58ad47](https://github.com/syofyanzuhad/uptime-kita/commit/a58ad477e2683b09067af9ba95edd097a172a9d9))
* **compact:** manually parse translatable tag names in raw engine ([7aa533a](https://github.com/syofyanzuhad/uptime-kita/commit/7aa533ad32318874b9f3c234134b370006aad518))
* **compact:** remove non-existent tags.color column from raw query ([535089f](https://github.com/syofyanzuhad/uptime-kita/commit/535089f01df13ba52c9fcc956a32edeeb49a52c7))
* **compact:** resolve 0% uptime bug by simplifying raw date query ([d9beee2](https://github.com/syofyanzuhad/uptime-kita/commit/d9beee292238e77c81413182cc8fc452470943e2))
* **compact:** resolve syntax error and duplicate code in MonitorCompactController ([f55f20a](https://github.com/syofyanzuhad/uptime-kita/commit/f55f20a61d054fa1d27c7d771015a18f456dc4ca))
* **compact:** use correct Collection search method for sorting ([83e4b37](https://github.com/syofyanzuhad/uptime-kita/commit/83e4b374b6e17ceba32ece447693c08830dc462c))
* **compact:** use correct display_name column for search query ([96eedc4](https://github.com/syofyanzuhad/uptime-kita/commit/96eedc4f330ad9a9761f05602aac230476803ef2))
* improve DetailMonitorModal robustness for SSL and Interval values ([28168f0](https://github.com/syofyanzuhad/uptime-kita/commit/28168f0aa3e04c2dc59799a2e77edad41f38f06d))
* improve notification rate limiting and error handling ([1ba21b2](https://github.com/syofyanzuhad/uptime-kita/commit/1ba21b2d5a160134352e34c004a8f3f31dcfc859))
* **nixpacks:** ensure nightwatch-agent log permissions and fix command path ([a373984](https://github.com/syofyanzuhad/uptime-kita/commit/a373984891311f63956e0c5c50c52672a90432f4))
* refactor CalculateMonitorStatisticsJob to use fan-out pattern to prevent timeouts ([c91089c](https://github.com/syofyanzuhad/uptime-kita/commit/c91089c5b6d22939f369e8ac72731d2d353928fe))
* refactor MonitorStatusChanged notification for robustness and reliability ([f248c41](https://github.com/syofyanzuhad/uptime-kita/commit/f248c4173d4e7139c6f59eae74ee8617f2756e8e))
* resolve MaxAttemptsExceededException in CalculateMonitorStatisticsJob ([7de473f](https://github.com/syofyanzuhad/uptime-kita/commit/7de473f4911b28bbf928817fe4a8decdf1d8992c))
* resolve scheduler failures and add error handling for uptime checks ([8593393](https://github.com/syofyanzuhad/uptime-kita/commit/8593393e24e773ab998b0736d7c3c3f07dc85efe))
* validate Telegram routes and disable on permanent failure ([88b5266](https://github.com/syofyanzuhad/uptime-kita/commit/88b5266f1f2eb64bca88a9f8313f547386c23c8a))


### Refactoring

* **route:** move compact view to /monitors public route ([c1a619a](https://github.com/syofyanzuhad/uptime-kita/commit/c1a619a5fa17f018aba8c06f89d29395b7ac0260))


### Styling

* **compact:** use idiomatic route() helper for navigation links ([141bea0](https://github.com/syofyanzuhad/uptime-kita/commit/141bea0636c6bd6b6bfe9f0bfcee6fc9e26080e4))
* fix linting issues in Vue components ([181a7c0](https://github.com/syofyanzuhad/uptime-kita/commit/181a7c04764d57ac86681942b059b0ab657c8a9d))


### Documentation

* add investigation log for 500 errors during monitor imports ([259e024](https://github.com/syofyanzuhad/uptime-kita/commit/259e024529165af89ae88a88d0fade06bae58084))
* Add version badge to README ([32aff69](https://github.com/syofyanzuhad/uptime-kita/commit/32aff69765be40abba69f4c17f30d82b504a84da))


### Performance

* **compact:** aggressive memory optimization for massive monitor datasets ([84e24de](https://github.com/syofyanzuhad/uptime-kita/commit/84e24dea2a2ca2ef18cba4f041d7758c12b3551e))
* **compact:** implement raw DB engine to load all 54k+ monitors without pagination ([2559315](https://github.com/syofyanzuhad/uptime-kita/commit/2559315d934020712803fcceedbe67752df80730))
* **compact:** implement Two-Step Fetch to prevent OOM on 54k+ monitors ([1e90cf3](https://github.com/syofyanzuhad/uptime-kita/commit/1e90cf39be965851cd20c92ffab2ada1cb22dbf6))
* **compact:** optimize monitor query to prevent OOM on large datasets ([e5e25cb](https://github.com/syofyanzuhad/uptime-kita/commit/e5e25cb9bf0672efe077cd8f177235568def27a9))
* **compact:** optimize SQLite query patterns to prevent 504 timeouts ([5752838](https://github.com/syofyanzuhad/uptime-kita/commit/57528381319d7042165670df051247b7b1efe8c6))
* **compact:** push monitors with null statistics to the bottom of sorted lists ([5493eba](https://github.com/syofyanzuhad/uptime-kita/commit/5493eba50630c9c04673ecdf53b2dc34d6c45eb5))
* **compact:** use SimpleMonitorResource and lean query for wallboard ([03b4bbc](https://github.com/syofyanzuhad/uptime-kita/commit/03b4bbcd149237fae9e6c0bd2f92251aee67922e))
* enable auto-balancing for statistics queue to save idle resources ([0b72338](https://github.com/syofyanzuhad/uptime-kita/commit/0b7233847e096ab86326cdb77f058bf7e50b7014))
* fix N+1 query issues in monitor listings and status pages ([0877c67](https://github.com/syofyanzuhad/uptime-kita/commit/0877c6798196bc65b047f7a0ee8294f622e225b6))
* implement monitor list caching and notification batching ([8a56fd5](https://github.com/syofyanzuhad/uptime-kita/commit/8a56fd50fe972b96d59ce9e7343fbcd6ecb88ccb))
* **nightwatch:** hardcode rejection for queries and cache events ([c54cb13](https://github.com/syofyanzuhad/uptime-kita/commit/c54cb13afda9b82e44903093747765a5e9e6b210))
* **nightwatch:** only report exceptions and error logs to reduce usage ([12c810f](https://github.com/syofyanzuhad/uptime-kita/commit/12c810f7a2fee9414a5a1639a465935298bb5908))
* **nightwatch:** optimize default filtering and sampling to reduce event volume ([33b7e5c](https://github.com/syofyanzuhad/uptime-kita/commit/33b7e5c55387d386989f153350ab1231de467384))
* offload database writes to queue and optimize real-time performance metrics ([74e0b58](https://github.com/syofyanzuhad/uptime-kita/commit/74e0b58df7cfacaaa710fe66a72d01c129db6b10))
* optimize CalculateMonitorStatisticsJob and add supporting indexes ([89ea5dd](https://github.com/syofyanzuhad/uptime-kita/commit/89ea5dd39dd87fea682d2cb468e0a4f5162b8400))
* optimize Horizon configuration for statistics and uptime calculations ([4da22c6](https://github.com/syofyanzuhad/uptime-kita/commit/4da22c614905930b22ac9fddfdbdd142106ae9a3))
* optimize monitor statistics job to eliminate fan-out and use daily rollups for 7d+ stats ([26c51a0](https://github.com/syofyanzuhad/uptime-kita/commit/26c51a0b4cf33e7f2e5be93dab1e96a4a61ecb97))
* reduce calculation frequency to resolve queue backlog ([abb8352](https://github.com/syofyanzuhad/uptime-kita/commit/abb83520467b71064df55e8d446ed12d64ada5a4))
* update default user-agent to browser string to bypass CDN blocks ([b36ddfd](https://github.com/syofyanzuhad/uptime-kita/commit/b36ddfd7abb82582f63c68f49907044a13c74d1a))


### Tests

* add coverage for MonitorCheckUptime command ([1804a36](https://github.com/syofyanzuhad/uptime-kita/commit/1804a36e42a9f24e005aafb19f29cec66825c40b))
* add tests for notification batching and wallboard caching ([e124c06](https://github.com/syofyanzuhad/uptime-kita/commit/e124c06a96a054851b2c94fd0e61c106593643f9))
* disable TraceReplay in testing environment to prevent 500 errors during file uploads ([7ac612f](https://github.com/syofyanzuhad/uptime-kita/commit/7ac612fab16577883adfc6ffa34d12a813bceb3f))
* fix failing test case in MonitorListControllerTest ([d34f24a](https://github.com/syofyanzuhad/uptime-kita/commit/d34f24adbd23a087056b47039e8082d922d13aca))
* update CalculateMonitorStatisticsJobTest to reflect sequential processing ([2158227](https://github.com/syofyanzuhad/uptime-kita/commit/21582276c3ff17b5def2dd568a4988935710b38b))
* update performance service tests to verify running average logic ([e672e8f](https://github.com/syofyanzuhad/uptime-kita/commit/e672e8f50bf298c14ae643d65cfdc45fa8f1ea55))


### Chores

* ai-docs ([016928a](https://github.com/syofyanzuhad/uptime-kita/commit/016928a0aeb4900beeca09880484f10e776e3128))
* include production logging in SendBatchedNotificationsJob ([80fbf89](https://github.com/syofyanzuhad/uptime-kita/commit/80fbf893f2043c50d93a7718c0301074c4eb9bc0))
* install laritor monitoring ([14ba110](https://github.com/syofyanzuhad/uptime-kita/commit/14ba110dac321eef5c509f4ff3d7a043fde849e5))
* install trace-replay ([aae0e99](https://github.com/syofyanzuhad/uptime-kita/commit/aae0e99cb07dce55da8c2f5919055ec1210eec54))
* **nixpacks:** remove non-existent nightwatch-agent worker ([f74ba61](https://github.com/syofyanzuhad/uptime-kita/commit/f74ba61e25f8312bf8d2945bbf0074ff6cc1ee78))
* **nixpacks:** resolve conflict by removing non-existent nightwatch-agent ([71528e3](https://github.com/syofyanzuhad/uptime-kita/commit/71528e3658adda6edd79060dbd6867c6860beb11))
* publish insight config ([e1a0195](https://github.com/syofyanzuhad/uptime-kita/commit/e1a01956cc88f3f8d5eeca02f6f09f404df02529))
* update dependencies including Laravel framework and Pest ([08fd4d3](https://github.com/syofyanzuhad/uptime-kita/commit/08fd4d3a4d66ad26d2152c87e75067780f91afec))
* update project configuration and dependencies ([77cda42](https://github.com/syofyanzuhad/uptime-kita/commit/77cda4240bf4320fe4b465b943deab574815a679))
* **wayfinder:** regenerate JS routes and actions ([02437d3](https://github.com/syofyanzuhad/uptime-kita/commit/02437d30b3542cdf666089cdae8f8e2064214cd7))
* **wayfinder:** sync JS routes after renaming compact route ([e7e30a3](https://github.com/syofyanzuhad/uptime-kita/commit/e7e30a3cdaa9c0d6275fd8ec384f74f6652fe0d3))

### [0.1.2](https://github.com/syofyanzuhad/uptime-kita/compare/v0.1.1...v0.1.2) (2025-12-26)

### [0.1.1](https://github.com/syofyanzuhad/uptime-kita/compare/v0.1.0...v0.1.1) (2025-12-26)


### Features

* Add anonymous telemetry system for tracking self-hosted instances ([2af2f65](https://github.com/syofyanzuhad/uptime-kita/commit/2af2f650c008c0d57b35c1bf0d48dd3aa9d3aaeb))
* Add AppearanceController for settings page ([c10115e](https://github.com/syofyanzuhad/uptime-kita/commit/c10115ec24d39810ff1fa9b94d8e68ecf246e3a8))
* add backup service to r2 storage ([b483ace](https://github.com/syofyanzuhad/uptime-kita/commit/b483acedbdd8014caea1f75caa5ab38ec5acc574))
* Add badge usage guide to public monitor and dashboard detail pages ([61d3297](https://github.com/syofyanzuhad/uptime-kita/commit/61d32976b633f5dca69eb601b0704aa905905374))
* Add bulk monitor import feature with CSV/JSON support ([0f5e000](https://github.com/syofyanzuhad/uptime-kita/commit/0f5e00040b18dc1f3210b7a703b63ebbb6c07fac))
* add daily uptime calculation command ([2dcefc9](https://github.com/syofyanzuhad/uptime-kita/commit/2dcefc9f8310c63913482031cd2fd723b33dfcf9))
* Add database backup and restore functionality ([b0e66f2](https://github.com/syofyanzuhad/uptime-kita/commit/b0e66f27ff65c4bed13f518766bdb5f89c65d671))
* add DatabaseCheck and update ScheduleCheck in AppServiceProvider health checks ([3718d16](https://github.com/syofyanzuhad/uptime-kita/commit/3718d16d56dd1c17aa5c57d9d018cd3ffd203ec2))
* Add DebugStatsController for monitoring statistics ([6abdc96](https://github.com/syofyanzuhad/uptime-kita/commit/6abdc964c34a04722308fc08ac627e1a478e5720))
* Add Docker support and cronless scheduler for production deployment ([9acfbfa](https://github.com/syofyanzuhad/uptime-kita/commit/9acfbfa812f234a707b9c32668a32182a177aef1))
* add health check route for unauthenticated access ([e6343e0](https://github.com/syofyanzuhad/uptime-kita/commit/e6343e0bca27438297935c6c341afb411818aace))
* add Laravel Boost guidelines and MCP server configuration ([bee05c0](https://github.com/syofyanzuhad/uptime-kita/commit/bee05c03957c6b11a4cecbc5c957340fbcaf3715))
* Add line chart visualizations for response time and uptime history ([4b31567](https://github.com/syofyanzuhad/uptime-kita/commit/4b315675dac01c76cb194a91c3118c5b093f2513))
* Add logging for Umami badge tracking ([c3d8877](https://github.com/syofyanzuhad/uptime-kita/commit/c3d88778b010c3203b3a76b16a633e279244eaef))
* Add new interfaces for monitors, status pages, and notification channels ([3a84632](https://github.com/syofyanzuhad/uptime-kita/commit/3a846328c3297c66a6a6aef154ea4e7dbb96d4ec))
* Add notification channels to user management ([906ef5b](https://github.com/syofyanzuhad/uptime-kita/commit/906ef5bdf19b8fe3b4b9f3e595283826269488a6))
* Add page view tracking for public monitor pages ([841cade](https://github.com/syofyanzuhad/uptime-kita/commit/841cade267ad7474d242dd08fa798c364aa427f4))
* Add real-time toast notifications for monitor status changes on public pages ([04f7401](https://github.com/syofyanzuhad/uptime-kita/commit/04f7401654dda062c8423c55182a71e570dee9cc))
* Add real-time toast notifications for monitor status changes on public pages ([9b8637c](https://github.com/syofyanzuhad/uptime-kita/commit/9b8637ca2273864991727833f13960a38bfa81b6))
* Add SEO friendly public pages with dynamic OG images and demo status page ([d7e9e57](https://github.com/syofyanzuhad/uptime-kita/commit/d7e9e576f92a0982eeed02105e66715d6c1bed8e))
* Add server resource transparency badge on public pages ([f3e00fb](https://github.com/syofyanzuhad/uptime-kita/commit/f3e00fb045d849e1ff3bba5e25ab341d28bee930))
* Add server resources monitoring page with auto-refresh ([a150389](https://github.com/syofyanzuhad/uptime-kita/commit/a150389381d2d5405eafae4f9e331339ccea8647))
* Add server-side OG meta tags for social media crawlers ([6add9e5](https://github.com/syofyanzuhad/uptime-kita/commit/6add9e5ce6e1afa080879e49df0bd09fdcd4be07))
* Add share button to main public page ([92e23f0](https://github.com/syofyanzuhad/uptime-kita/commit/92e23f03e7d6ed5f60e37b482b429b5364cc26f6))
* Add share button to status page and fix navbar layout ([5025b19](https://github.com/syofyanzuhad/uptime-kita/commit/5025b19ed44beddb3ca22845a78bd1728eb90eae))
* Add smart alert algorithm to reduce false positives and maintenance windows ([a5d99b3](https://github.com/syofyanzuhad/uptime-kita/commit/a5d99b3f29af5408b520cd7631e9228c91c6c615))
* Add smart retry with exponential backoff to prevent false positives ([7e2b85c](https://github.com/syofyanzuhad/uptime-kita/commit/7e2b85cd927014f1ada400d38e3da4b81b83e0d9))
* Add sorting and view count display to public monitors list ([380bb16](https://github.com/syofyanzuhad/uptime-kita/commit/380bb16f8e5a1b04934ee027ce4ecdbd46a17ef7))
* Add spatie/laravel-cronless-schedule and clue/reactphp libraries ([e2eb737](https://github.com/syofyanzuhad/uptime-kita/commit/e2eb7376f4473a3577e9bd87d831b4e22b1383fa))
* Add Umami analytics tracking for badge views ([5718b53](https://github.com/syofyanzuhad/uptime-kita/commit/5718b53734a252434dbc67902442515710a71bd7))
* Add uptime badge endpoint for embedding in README/websites ([67edbae](https://github.com/syofyanzuhad/uptime-kita/commit/67edbaebc7aa26309a7723462a065ccbdc4d1242))
* add uptime kita telegram bot on notification form ([8367aa7](https://github.com/syofyanzuhad/uptime-kita/commit/8367aa7fb372e1f56de34235441f74b89d06eaee))
* **bookmark:** enable uptime check for monitors and update pinned status in tests ([f976b3c](https://github.com/syofyanzuhad/uptime-kita/commit/f976b3cd1acdc1321f8edeac3948448a7b6c1221))
* **components:** add ExternalLink component for better SEO and accessibility ([f3e2697](https://github.com/syofyanzuhad/uptime-kita/commit/f3e2697fcf51b14c522fa5d9062d08b29e6be839))
* **components:** add GitHub icon to PublicFooter component ([46b3088](https://github.com/syofyanzuhad/uptime-kita/commit/46b3088654b031df37f52ef7e759f0670f270926))
* **components:** create reusable PublicFooter component for public pages ([a13f1df](https://github.com/syofyanzuhad/uptime-kita/commit/a13f1df69c1bc26c5ae03ba2c3c02a05ba815508))
* **custom-domain:** implement custom domain management for status pages ([63775ea](https://github.com/syofyanzuhad/uptime-kita/commit/63775ea609a12bb72e974030e0dc5521e1d35052))
* **dependencies:** add laravel/boost package to development requirements ([490297f](https://github.com/syofyanzuhad/uptime-kita/commit/490297f0f231f9a1a6b50a24fb18e54a46633996))
* Enhance CalculateMonitorStatisticsJob for improved performance and error handling ([5645d54](https://github.com/syofyanzuhad/uptime-kita/commit/5645d5493b770790eb0b19b2ca934506d892539c))
* enhance health checks in AppServiceProvider with additional checks for CPU load, cache, Redis, and queue ([7a913fd](https://github.com/syofyanzuhad/uptime-kita/commit/7a913fde1623704816456bcbf4b65a4cf1b06a43))
* Enhance public monitors index with statistics and mobile-friendly UI ([842078f](https://github.com/syofyanzuhad/uptime-kita/commit/842078f83779d51f3d7a6334c8e98d4860ff6648))
* enhance uptime calculation jobs with batch processing and improved error handling ([6c6e784](https://github.com/syofyanzuhad/uptime-kita/commit/6c6e784644940435633883e83a6640a8cd242568))
* Enhance user detail view with related data ([429b302](https://github.com/syofyanzuhad/uptime-kita/commit/429b3022b283f746c69ca118c42b0502f53434a5))
* **external-link:** enhance ExternalLink component with referrer tracking capabilities ([2645dc4](https://github.com/syofyanzuhad/uptime-kita/commit/2645dc4fea576b3e785645365f006498922efd65))
* **factory:** add admin user state to UserFactory ([2123797](https://github.com/syofyanzuhad/uptime-kita/commit/2123797629940e5ecd7e98bdc02de488ffc9dda0))
* **images:** add new uptime-kita image and update reference in PublicIndex.vue ([852276b](https://github.com/syofyanzuhad/uptime-kita/commit/852276bc0bc6f4052b9df17becd2f2023194c31a))
* Make latest incidents clickable on public index page ([c2587c1](https://github.com/syofyanzuhad/uptime-kita/commit/c2587c13058ea218da8955e08a464941ae1d1867))
* Make status page share text dynamic with actual status ([1c66a48](https://github.com/syofyanzuhad/uptime-kita/commit/1c66a48c2f9c3142604621340446a9d6b257e13f))
* **middleware:** add CustomDomainMiddleware for handling custom domains ([712d46a](https://github.com/syofyanzuhad/uptime-kita/commit/712d46adf667736964218c077c9c55f5d72269b7))
* **migrations:** add performance indexes to multiple tables ([1cbca0c](https://github.com/syofyanzuhad/uptime-kita/commit/1cbca0c4fd73ae4f758784f57981893875853339))
* **monitor-performance:** enhance uptime calculation and store monitor check data ([afe02fb](https://github.com/syofyanzuhad/uptime-kita/commit/afe02fb6627c9079436d51cbfb5926e1195dd1cf))
* **monitor:** add back to top button functionality in PublicIndex.vue ([b16facf](https://github.com/syofyanzuhad/uptime-kita/commit/b16facf65cc643bde7f789e989102536c54c5fb7))
* **monitor:** add commands for cleaning up duplicate monitor histories and enforce unique constraints ([1712c9a](https://github.com/syofyanzuhad/uptime-kita/commit/1712c9a60bc3bfc98213bea9387c779daa35f7ec))
* **monitor:** add host attribute to Monitor model and update resource and public index ([941eb87](https://github.com/syofyanzuhad/uptime-kita/commit/941eb8717f24cfaee006794127d58a1600560813))
* **monitor:** add Monitor model factory and comprehensive CRUD tests for user monitor management ([1b7c32b](https://github.com/syofyanzuhad/uptime-kita/commit/1b7c32be139023b61b9520186be94175f2a44497))
* **monitor:** add pinned and not pinned scopes to Monitor model ([f46543c](https://github.com/syofyanzuhad/uptime-kita/commit/f46543c0542b9d02b11f332d1f6fb213ade8d72c))
* **monitor:** add shadcn table components and numbering to history table ([4516439](https://github.com/syofyanzuhad/uptime-kita/commit/451643979dbdf535a6776921aa67ba889b884ded))
* **monitor:** add tag filtering to public monitor index ([a3da97f](https://github.com/syofyanzuhad/uptime-kita/commit/a3da97f2c7627474efc843e31e085b8eaca53b69))
* **monitor:** change back button to link to home page ([2cfc018](https://github.com/syofyanzuhad/uptime-kita/commit/2cfc018de674f04d6b40c7a042c392c467ef8637))
* **monitor:** enhance monitor functionality with tag support ([a25801d](https://github.com/syofyanzuhad/uptime-kita/commit/a25801d196773c8211b0b65e7ace06ce946c2680))
* **monitor:** enhance Monitor model with new fields and relationships ([21ddcae](https://github.com/syofyanzuhad/uptime-kita/commit/21ddcaeec52ad6e041d87db13e2c278d611bcc71))
* **monitor:** enhance performance data retrieval with short caching ([2220c0b](https://github.com/syofyanzuhad/uptime-kita/commit/2220c0b031f38dac095948189ad9091a053e1b4a))
* **monitor:** enhance public monitor index with tag filtering ([eee4d9a](https://github.com/syofyanzuhad/uptime-kita/commit/eee4d9a42fe0abd9e347cc4b75c110598a2e4e08))
* **monitor:** enhance PublicIndex.vue with improved mobile and desktop views ([602970c](https://github.com/syofyanzuhad/uptime-kita/commit/602970c4241ec68770a731ba9514077c146a142e))
* **monitor:** enhance toggle pin functionality in PinnedMonitorController ([7e88fe8](https://github.com/syofyanzuhad/uptime-kita/commit/7e88fe85aa10ef461dfe3b60ae7040d3114c6aa6))
* **monitor:** enhance user interface with tooltips for improved accessibility and information display ([e77c547](https://github.com/syofyanzuhad/uptime-kita/commit/e77c547a0b8e933ea67f727159ac06baf5253532))
* **monitor:** implement 100-minute uptime history visualization and improve data handling ([7819f7a](https://github.com/syofyanzuhad/uptime-kita/commit/7819f7a47ab64d53785183006a3f1577ecaa3b65))
* **monitor:** implement unique history retrieval per minute for improved data accuracy ([5ff1f80](https://github.com/syofyanzuhad/uptime-kita/commit/5ff1f8059b9a828e705f5ddfe6eea33e5b136951))
* **monitor:** optimize recent history retrieval with unique IDs per minute ([39ca7b9](https://github.com/syofyanzuhad/uptime-kita/commit/39ca7b9823e63802810871b6268597e16781792f))
* **monitors:** add back icon to public monitor show page ([dc623a8](https://github.com/syofyanzuhad/uptime-kita/commit/dc623a8170201757e71ebca15a4abe22edf2d89d))
* **notification:** add NotificationChannel model with factory and CRUD tests for user notification channels ([91fadef](https://github.com/syofyanzuhad/uptime-kita/commit/91fadef812263776c18499b090ad605451a6410a))
* **pagination:** implement reusable Pagination component ([1562d81](https://github.com/syofyanzuhad/uptime-kita/commit/1562d81ca007dfd4512ff25aa72e5c7db49250a9))
* **pinned-monitors:** implement PinnedMonitorController and UI components ([89ed1e1](https://github.com/syofyanzuhad/uptime-kita/commit/89ed1e137c6c8a2776854605a1d65acc08ac61bb))
* **public-monitor:** add PublicMonitorShowController and public monitor page ([6155510](https://github.com/syofyanzuhad/uptime-kita/commit/6155510cbf31f0ee065c9ee9f3a85cd39bb9a4ce))
* **public-monitor:** enhance PublicMonitorShowController and Vue component for performance metrics ([4c117ef](https://github.com/syofyanzuhad/uptime-kita/commit/4c117efce2e9e31a984f08b42388b7564ba0f2cd))
* **PublicIndex.vue:** add create monitor button and navigation ([754ece4](https://github.com/syofyanzuhad/uptime-kita/commit/754ece4126a9f307226278a1bdd210a632bc2bd1))
* **PublicMonitorController, PublicIndex.vue:** implement public monitors listing with pagination and filtering ([4f74340](https://github.com/syofyanzuhad/uptime-kita/commit/4f743409561ff7183234454cae2822cbdf861935))
* **PublicMonitorShowController:** add not found handling and new Vue component ([6d65e02](https://github.com/syofyanzuhad/uptime-kita/commit/6d65e023d514fed3f39bb719893cd3921cd54b8c))
* **PublicShow.vue:** add theme toggle and footer to monitor display ([79b467e](https://github.com/syofyanzuhad/uptime-kita/commit/79b467e7a21cb49bfc2c2dcd57f33c03a863442a))
* **PublicShow.vue:** implement latest 100 minutes history bar with auto-refresh ([e83e603](https://github.com/syofyanzuhad/uptime-kita/commit/e83e6034be2ae692387bea386656427af6815817))
* Restrict Server Resources page to admin users only ([e772332](https://github.com/syofyanzuhad/uptime-kita/commit/e77233299f32452f864e3fa66f09ada93a8299dd))
* Show period duration in badge label (e.g. "uptime 24h") ([b849562](https://github.com/syofyanzuhad/uptime-kita/commit/b849562735bd17bf3911fa2c3a8779b0b135d0ae))
* **statistics:** add command to calculate and cache monitor statistics for public monitors ([0ea0481](https://github.com/syofyanzuhad/uptime-kita/commit/0ea04815313f8f551bb13198db696775de97edf9))
* **status-page:** add custom domain support and verification features ([8661c7e](https://github.com/syofyanzuhad/uptime-kita/commit/8661c7ee9c428a9ee8c8fff59ff69aa2579b3db4))
* **status-page:** implement CRUD operations for status pages with associated factory and model updates ([22e71f3](https://github.com/syofyanzuhad/uptime-kita/commit/22e71f3d99454f2a898d458a18365d6f3d4d6b1a))
* **tags:** add TagController for managing tags and implement tag retrieval and search functionality ([4480e3e](https://github.com/syofyanzuhad/uptime-kita/commit/4480e3ea7782fbc53362aa14703cd6de9cee85cc))
* **tags:** implement TagInput component for dynamic tag management ([1527179](https://github.com/syofyanzuhad/uptime-kita/commit/152717945bc8e482a7de57a36bce8b80fa95232a))
* **tags:** integrate tag management in uptime forms and index ([1c4bc21](https://github.com/syofyanzuhad/uptime-kita/commit/1c4bc21ae71b808873a16d80833bebcde8a066d1))
* telegram webhook ([5e93513](https://github.com/syofyanzuhad/uptime-kita/commit/5e93513ccc58e09df39afed1053f47ab8efe0e1a))
* **ui:** add new user management pages including create, edit, and show functionalities ([dbe33f9](https://github.com/syofyanzuhad/uptime-kita/commit/dbe33f9da7fb8d3d33424faa66e1b4f0dac4997a))
* **ui:** implement shadcn table components across all tables in the application ([4ecd6a0](https://github.com/syofyanzuhad/uptime-kita/commit/4ecd6a09e5097e984e18d18b3e6893a3d0df05e6))
* **uptime:** add unique tag filtering to Uptime Monitor index ([d31e8ac](https://github.com/syofyanzuhad/uptime-kita/commit/d31e8ac6feaa0d8f577663f26178e52e9148978a))
* **user:** implement search and pagination features in UserController and Index.vue ([7da11b0](https://github.com/syofyanzuhad/uptime-kita/commit/7da11b0fdeaf28d3f5d942e2d459e0758e30fb79))
* **users:** enhance user index with monitor and status page counts ([3e14faa](https://github.com/syofyanzuhad/uptime-kita/commit/3e14faac38c4ed6f34418757a196079c7a73b50e))


### Bug Fixes

* **accessibility:** add object-cover to maintain image aspect ratio ([751fef5](https://github.com/syofyanzuhad/uptime-kita/commit/751fef560d91f8b463541dc1d5d86c734ef26b84))
* **accessibility:** resolve accessibility issues in PublicIndex page ([d8762ff](https://github.com/syofyanzuhad/uptime-kita/commit/d8762ffcf81cc2699f22d346a6ff7fac293299dd))
* Add required User-Agent header for Umami tracking ([e9cc489](https://github.com/syofyanzuhad/uptime-kita/commit/e9cc4894c4fa599231b8fb2dc19a08ea3b58e1bb))
* allow redirect uptime ([8115991](https://github.com/syofyanzuhad/uptime-kita/commit/811599121266265467b41bc9922ad6e213ffef88))
* **app:** correct case in component path for consistency in asset loading ([3bc4824](https://github.com/syofyanzuhad/uptime-kita/commit/3bc4824243c028f6d659cf4f55112d749f54d908))
* **AppSidebar.vue:** update logo link in sidebar for consistent navigation ([609711c](https://github.com/syofyanzuhad/uptime-kita/commit/609711c502f4d5c9e1b794faf3f9825bb4690141))
* **app:** update component path to lowercase for consistency in asset loading ([262f691](https://github.com/syofyanzuhad/uptime-kita/commit/262f691ae7490bec1c0d497c3c60e6145c30ef1f))
* backup config ([30c75a4](https://github.com/syofyanzuhad/uptime-kita/commit/30c75a48c574c466fb9f0049ac53bb43bd7389fa))
* backup config ([3f3be45](https://github.com/syofyanzuhad/uptime-kita/commit/3f3be45c88c5afe83015bcda72db5f2ac04ca78e))
* Change default sort to by ID instead of newest ([85fcca2](https://github.com/syofyanzuhad/uptime-kita/commit/85fcca29cdc15b766af392ea0cb06f4fb4de3754))
* correct case sensitivity issues for Pages directory ([0474984](https://github.com/syofyanzuhad/uptime-kita/commit/04749841d38de34ebf8dab43af109bf5e83dd9d6))
* Correct logic for public monitor notifications in MonitorStatusChanged ([ba2c4cb](https://github.com/syofyanzuhad/uptime-kita/commit/ba2c4cb88ee9c525e0a3f6baf449938eff9fe2fd))
* csrf exception for webhook ([034f73f](https://github.com/syofyanzuhad/uptime-kita/commit/034f73f57a451f87984cda55cdb19eafa17bf5c8))
* db config ([a56f9ae](https://github.com/syofyanzuhad/uptime-kita/commit/a56f9ae422c94df159f1380af7dcbcfdff3fbc78))
* db-sqlite queue, telescope ([b586e4d](https://github.com/syofyanzuhad/uptime-kita/commit/b586e4deeeef0811b17804feb3c66501083cdc3a))
* Enhance Twitter notification logic in MonitorStatusChanged to respect rate limits ([be1b106](https://github.com/syofyanzuhad/uptime-kita/commit/be1b1062c69f874d432e4fb8d645a8727c13e933))
* horizon ([7401fc7](https://github.com/syofyanzuhad/uptime-kita/commit/7401fc75b78ee7a84b61a9273dcc438202e45a26))
* Improve badge SVG text width calculation to prevent truncation ([6d500bd](https://github.com/syofyanzuhad/uptime-kita/commit/6d500bdc92b3421c29b11c67598a1da6161a5f6d))
* Improve Twitter notification handling in MonitorStatusChanged ([6d425a5](https://github.com/syofyanzuhad/uptime-kita/commit/6d425a54b6c56843d0378872e66bdf8e59d45b5d))
* job ([5ae31e5](https://github.com/syofyanzuhad/uptime-kita/commit/5ae31e57e06f9e61a0ca5f56e0f20731b52af3d9))
* job ([247e0f1](https://github.com/syofyanzuhad/uptime-kita/commit/247e0f17d2990af3425f81e795fdd37fc54922d2))
* job ([10b1bae](https://github.com/syofyanzuhad/uptime-kita/commit/10b1bae407a597add2a7c23cd2f056066d1bee41))
* job ([e9ab232](https://github.com/syofyanzuhad/uptime-kita/commit/e9ab232aabb5c1b15f0b2b19f0480bc751d559b0))
* jobs ([12803ad](https://github.com/syofyanzuhad/uptime-kita/commit/12803ad5a3fd63c096bda03bc0654d11bfa575ea))
* jobs ([ff9081a](https://github.com/syofyanzuhad/uptime-kita/commit/ff9081ac45eb3d2b617c7815151d5cded05562b6))
* jobs ([ad53ec0](https://github.com/syofyanzuhad/uptime-kita/commit/ad53ec023888e9a26344d2d29f226c778c7cf1d9))
* **layout:** adjust icon size and debounce delay in PublicIndex.vue for improved UI responsiveness ([388865b](https://github.com/syofyanzuhad/uptime-kita/commit/388865b94e9b8aef7269516cc5ac30c9ee1ae85e))
* **layout:** adjust spacing for FlashMessage and Dashboard components ([ae3ff8f](https://github.com/syofyanzuhad/uptime-kita/commit/ae3ff8fd14540fa970f1824df51607dd6883bc0a))
* monitor model ([75534cd](https://github.com/syofyanzuhad/uptime-kita/commit/75534cd4d0e8391d9b01f45429b330b2a7098246))
* monitor private ([7afd560](https://github.com/syofyanzuhad/uptime-kita/commit/7afd5608bc3f968b78f314126eb56232cd81712b))
* **monitor:** change route parameter from {monitor} to {monitorId} for toggle-pin endpoint ([b8cdcd7](https://github.com/syofyanzuhad/uptime-kita/commit/b8cdcd7ed1f1a4581473e7367457338d0f882b10))
* **monitor:** enhance authorization checks in destroy method ([c2d4afe](https://github.com/syofyanzuhad/uptime-kita/commit/c2d4afe8301310c9ad64de6d29c32863ce5ec0fc))
* **monitor:** enhance global scope to check user authentication ([5081570](https://github.com/syofyanzuhad/uptime-kita/commit/50815706d9cf6f820ce3954cad84839b0623b073))
* **monitor:** enhance PublicIndex.vue with link truncation and clean up whitespace ([5d53bd8](https://github.com/syofyanzuhad/uptime-kita/commit/5d53bd889df97c2f98c2015b3761a13d90eb06cd))
* **monitor:** enhance PublicIndex.vue with uptime and last check details ([167e2cb](https://github.com/syofyanzuhad/uptime-kita/commit/167e2cbf4842a427669a3487c49eaeb5a55c9b78))
* **monitor:** ensure user attachment only occurs for authenticated users ([b3842b1](https://github.com/syofyanzuhad/uptime-kita/commit/b3842b1dfdc8e2d683b587fdc2db6607a1e4c08b))
* **monitor:** fix authorization policy and route parameter issues ([7072eb7](https://github.com/syofyanzuhad/uptime-kita/commit/7072eb7400e151c9164ae4baf2d1dcfc1f74305f))
* **monitor:** improve cache handling and authorization in monitor operations ([bdaf79c](https://github.com/syofyanzuhad/uptime-kita/commit/bdaf79caa7eefcb8bd76004c2aef461d85077eec))
* **monitor:** improve load more functionality and data handling in PublicIndex.vue ([68b4e52](https://github.com/syofyanzuhad/uptime-kita/commit/68b4e52a96de466b1343f5d7a32cf60d892e6aa1))
* **monitor:** sanitize and validate URL in store method ([4027d99](https://github.com/syofyanzuhad/uptime-kita/commit/4027d99f1a631746ec38c9e38dcc85d134f371f6))
* **monitor:** update global scope to exclude admin users from query ([b6510bd](https://github.com/syofyanzuhad/uptime-kita/commit/b6510bd7259290869de827a933dab8fbf82571e3))
* notification form ([4bfdcef](https://github.com/syofyanzuhad/uptime-kita/commit/4bfdcef9981df99fa047c64519b51322e4574b51))
* npm run lint ([2980a71](https://github.com/syofyanzuhad/uptime-kita/commit/2980a7176819a619d72f53d539f89585fd8a2ab7))
* **policy:** restrict update and delete actions for public and private monitors to admins and owners respectively ([1fb9b98](https://github.com/syofyanzuhad/uptime-kita/commit/1fb9b9884f907008582f0cbf62639cd000f77391))
* Prevent duplicate CalculateMonitorStatisticsJob instances ([150f282](https://github.com/syofyanzuhad/uptime-kita/commit/150f28239c9e790016005fd4d37e6906416981c4))
* Prevent Twitter notifications for public monitors in MonitorStatusChanged ([0338e4c](https://github.com/syofyanzuhad/uptime-kita/commit/0338e4c2e0b19e3fa266f7b1c12611d621fd99f9))
* **PublicIndex.vue, PublicShow.vue, Create.vue:** update footer links and enhance monitor visibility options ([b220518](https://github.com/syofyanzuhad/uptime-kita/commit/b2205189df6ae87927529ab1f61c545c2d996bb6))
* **PublicIndex.vue, PublicShow.vue, PublicShowNotFound.vue:** update footer links for consistent navigation ([2ecbbcf](https://github.com/syofyanzuhad/uptime-kita/commit/2ecbbcf9fa9897f5efabc0a246a76ca66423cd3f))
* **PublicMonitorsCard.vue:** enhance fetchPublicMonitors function with headers and clean up code ([a78803d](https://github.com/syofyanzuhad/uptime-kita/commit/a78803da1abb9bc420f57ddf80154a04667dfac3))
* **PublicShow.vue:** adjust width of history bar for better responsiveness ([37acf1b](https://github.com/syofyanzuhad/uptime-kita/commit/37acf1b3bdc1d13c0e6b0a9b226d0d8aad7477d8))
* **PublicShow.vue:** improve layout and spacing for header and footer ([2856911](https://github.com/syofyanzuhad/uptime-kita/commit/2856911a1c16449072250a1ba5a1a2b88ad0990b))
* **PublicShow.vue:** update incident timestamp display to show creation date ([4f152a8](https://github.com/syofyanzuhad/uptime-kita/commit/4f152a8d00946533861540fc333c13aef6dd7a61))
* **PublicShow:** remove unnecessary whitespace for cleaner code formatting ([2a594ca](https://github.com/syofyanzuhad/uptime-kita/commit/2a594ca6df8c89cfd2fc9ac5b6b7f976bd2a08b6))
* readme db ([46119d7](https://github.com/syofyanzuhad/uptime-kita/commit/46119d712eaf0a71e0880d2fcb0f528e1868eb91))
* Refine Twitter notification handling in MonitorStatusChanged ([d7dd450](https://github.com/syofyanzuhad/uptime-kita/commit/d7dd4509202a9015a803ed981961ff58b63e9862))
* Remove invalid --no-parallel option from Pest command ([58240a2](https://github.com/syofyanzuhad/uptime-kita/commit/58240a287e7dc672de2dc223dcce8f7c9c34b3fd))
* Remove unused variables to fix eslint errors ([7fde0ce](https://github.com/syofyanzuhad/uptime-kita/commit/7fde0ce07f8959bdff8cf74481b31623b2406fc3))
* Replace corrupt font files with valid Inter TTF fonts ([68530c1](https://github.com/syofyanzuhad/uptime-kita/commit/68530c1d9ba98e9abf20a7d6fa3eccdf4f15b89b))
* resolve case sensitivity issues for Pages directory on Linux ([b8d27ea](https://github.com/syofyanzuhad/uptime-kita/commit/b8d27eabcdbf8e49e165bbce831c5377e241104c))
* Resolve lint errors and test database configuration ([5019ec1](https://github.com/syofyanzuhad/uptime-kita/commit/5019ec135c0b1172b0d145725c9211c54e8ea66a))
* Resolve MaxAttemptsExceededException in CalculateMonitorStatisticsJob ([8b600b5](https://github.com/syofyanzuhad/uptime-kita/commit/8b600b57bc0bbb7faf0cb4d35e06462946983ca5))
* Resolve RefreshDatabase trait collision in tests ([7791fb5](https://github.com/syofyanzuhad/uptime-kita/commit/7791fb51cc2b425108d492558df0c3afe332e836))
* **search:** update reactive data when search results change ([687dc7e](https://github.com/syofyanzuhad/uptime-kita/commit/687dc7eea26daa0f5b8daa7aae99ec16c5c7bae2))
* Simplify badge SVG generation without complex scaling ([519f50e](https://github.com/syofyanzuhad/uptime-kita/commit/519f50e37a86445722655c61b573b9dfff7d533d))
* single job ([1b81b8f](https://github.com/syofyanzuhad/uptime-kita/commit/1b81b8f622b2902a274bd4439206771d93c6ec3e))
* Skip database backup tests that break RefreshDatabase isolation ([e5c6d4b](https://github.com/syofyanzuhad/uptime-kita/commit/e5c6d4babbb1973b882bf0b6638d34c16b82c7ae))
* Skip Twitter notification when credentials are not configured ([656b42c](https://github.com/syofyanzuhad/uptime-kita/commit/656b42ca6050be9afd12a355b1eec67766b71631))
* standardize case for Pages directory in Vite directive ([1adeab1](https://github.com/syofyanzuhad/uptime-kita/commit/1adeab1ab3ed9ac90459eece83c7367fb51fca22))
* telegram webhook ([95bf103](https://github.com/syofyanzuhad/uptime-kita/commit/95bf103153236ef7f604180989481d2c0fdb6fd3))
* telegram webhook ([48929c6](https://github.com/syofyanzuhad/uptime-kita/commit/48929c677d22457a6764d62c7d6d8bd7d86070fc))
* telegram webhook ([6101391](https://github.com/syofyanzuhad/uptime-kita/commit/6101391d867c148c860d0907b861991a16835070))
* telegram webhook ([830c066](https://github.com/syofyanzuhad/uptime-kita/commit/830c066501407aa5206cae6c352f57b4da66e4fc))
* telegram webhook ([dc3e9ab](https://github.com/syofyanzuhad/uptime-kita/commit/dc3e9abb36ea4d0a820583434bdd3a970bdcce0d))
* **tests:** update BookmarkTest and MonitorTagTest for improved assertions ([b82bc6a](https://github.com/syofyanzuhad/uptime-kita/commit/b82bc6aae275f5d28203308cf04c5476ec850d13))
* **ui:** adjust overflow behavior in Show.vue to improve table visibility ([a6dc65d](https://github.com/syofyanzuhad/uptime-kita/commit/a6dc65d29981c839e7fed252d9f5ce2803901526))
* Update icon name for certificate status in Public.vue ([0f9120d](https://github.com/syofyanzuhad/uptime-kita/commit/0f9120da1f619a4879c37b611e9bde7ddd2403d4))
* Update monitor URL handling in user detail view ([7dfd806](https://github.com/syofyanzuhad/uptime-kita/commit/7dfd806c79418cf490f393152af4c272a7dadf68))
* Update public visibility for monitor notifications in TwitterNotificationTest ([e1a7ca1](https://github.com/syofyanzuhad/uptime-kita/commit/e1a7ca1cd3acb0331545030680f2b936b8c0d1bb))
* Update Ziggy routes for improved consistency ([4b2cba3](https://github.com/syofyanzuhad/uptime-kita/commit/4b2cba3408247755f91689842af82ac673f9d099))
* Use extensions rule for database restore file validation ([d327f14](https://github.com/syofyanzuhad/uptime-kita/commit/d327f142d71a1cc5550b53e34ade6013784e0f82))
* Use raw_url instead of url object for incident navigation ([3bd47de](https://github.com/syofyanzuhad/uptime-kita/commit/3bd47de9b03579f4e9c84759baf26a99b4e936f0))
* **user:** correct error message handling in user deletion and add comprehensive CRUD tests for user management ([c49047e](https://github.com/syofyanzuhad/uptime-kita/commit/c49047e01c6fd6273ad55c42175d90a362101cb2))


### Tests

* **bookmark:** clear cache before running unpin monitor test ([e75acf2](https://github.com/syofyanzuhad/uptime-kita/commit/e75acf2b7f03062fb43cae0f676fbdce4cd5709c))
* calculate fifteen minutes ([1b2bc29](https://github.com/syofyanzuhad/uptime-kita/commit/1b2bc291de52063f6e7cfc665308cedd51ce7db2))
* **disabled-monitor:** change response method to getJson for disabled filter test ([1968091](https://github.com/syofyanzuhad/uptime-kita/commit/196809166429a33233e91820276c31da8852cbe3))
* fix ([b40b23d](https://github.com/syofyanzuhad/uptime-kita/commit/b40b23d45e8618e95c8fb0bfb754290e0094dec6))
* **global-filter:** update response method to getJson for globally enabled filter test ([587eb66](https://github.com/syofyanzuhad/uptime-kita/commit/587eb66678bff1346a4cebbc406e49a053b72890))
* **unsubscribe-monitor:** update flash message assertion in unsubscribe test ([110dc45](https://github.com/syofyanzuhad/uptime-kita/commit/110dc459c12a94b08cba37fd52651ae27d0d6154))
* uptime check 1 minute ([17608b9](https://github.com/syofyanzuhad/uptime-kita/commit/17608b9d3d9feef7d7908f0eb4e278c11aad11ad))
* uptime config ([7fabeb7](https://github.com/syofyanzuhad/uptime-kita/commit/7fabeb7e6baa721245b9f9a5fda9388f60cd2fd9))


### Styling

* **components:** update text color in PublicFooter and fix alt text in PublicShow component ([af44e9d](https://github.com/syofyanzuhad/uptime-kita/commit/af44e9d901f2d431633ba45ce0f8cb7a7d884c64))
* Fix code formatting with Laravel Pint ([21b62c4](https://github.com/syofyanzuhad/uptime-kita/commit/21b62c4f52667892b95f9ae744d27d1b57e86eb4))
* **monitors:** add shadow to favicon in public index page ([bada0e8](https://github.com/syofyanzuhad/uptime-kita/commit/bada0e81f0b86f83855e15fe61604dacbbfe0a56))
* **monitors:** use drop-shadow to follow PNG favicon shape ([9474832](https://github.com/syofyanzuhad/uptime-kita/commit/94748324942e7f726309097dfc703ac01eaa164c))
* **PublicIndex.vue:** adjust favicon and icon sizes for improved layout consistency ([3735072](https://github.com/syofyanzuhad/uptime-kita/commit/3735072cb094f29d13e8b91a981fbaa163ef51be))
* **ui:** enhance table header and row styles for improved visual consistency and accessibility ([d673d93](https://github.com/syofyanzuhad/uptime-kita/commit/d673d93a9e8ba9ac6c2209e72eb7b9937aaee31f))


### Chores

* Add CONTRIBUTING.md file and update README.md to link to it ([a71329e](https://github.com/syofyanzuhad/uptime-kita/commit/a71329e0aebd8084407d3935c5a82a36f2332d31))
* add doctrine/dbal dependency to composer.json and update composer.lock ([f8bfa92](https://github.com/syofyanzuhad/uptime-kita/commit/f8bfa92fcf8431b6cda14018ee549521ea8c891b))
* add repo activity on readme ([eda32b8](https://github.com/syofyanzuhad/uptime-kita/commit/eda32b8cfb19f4c79a61edf49f0d749397872809))
* add spatie/cpu-load-health-check dependency to composer.json and update composer.lock ([18d14e1](https://github.com/syofyanzuhad/uptime-kita/commit/18d14e17122b5f99ce6ee685f9ab43bf98277897))
* add status page demo page ([d1e2bdf](https://github.com/syofyanzuhad/uptime-kita/commit/d1e2bdf31a78ba7340a965434f44a1a4fd9903d6))
* **ai:** serena update ([35ecce3](https://github.com/syofyanzuhad/uptime-kita/commit/35ecce3fcba47b67a26ba591c7c706ea9b532e42))
* calculate every five minutes ([4785cfe](https://github.com/syofyanzuhad/uptime-kita/commit/4785cfef67be4574d0739590a0e1e0c1fca559e0))
* check uptime every fifteen minutes ([b6e4d47](https://github.com/syofyanzuhad/uptime-kita/commit/b6e4d47b38315f6d365c91bdedf18051b1caa8e5))
* composer update ([d9cc866](https://github.com/syofyanzuhad/uptime-kita/commit/d9cc86614b3c680dbc44e128d9511c52a6daf4b3))
* create a monitor component to be used for both public and private monitors components ([0febe13](https://github.com/syofyanzuhad/uptime-kita/commit/0febe137a2b44a14640241d805edaf3eddf6419b))
* cursor-ppointer on toggle theme ([e549c99](https://github.com/syofyanzuhad/uptime-kita/commit/e549c99c1d52b87ce2febb35f021982a38a3a0b9))
* **database:** comment out MonitorSeeder and NotificationChannelSeeder in DatabaseSeeder ([6e973b3](https://github.com/syofyanzuhad/uptime-kita/commit/6e973b3b3951f472efa711d5aaf483ad3f548bdc))
* **docs:** update readme ([57d9b78](https://github.com/syofyanzuhad/uptime-kita/commit/57d9b78c11c3cbafd31d3b61e4a8ff08ffc877c4))
* drob table on database.sqlite ([87c63fb](https://github.com/syofyanzuhad/uptime-kita/commit/87c63fb84eb268c7e6f8d40cf5c7f882b87c9432))
* enhance development workflow with PR checks and auto-formatting ([b15e675](https://github.com/syofyanzuhad/uptime-kita/commit/b15e6750d946eb8b7a6d328b396f1ec192bf471c))
* Exclude dev routes from Ziggy and auto-generate on build ([9523900](https://github.com/syofyanzuhad/uptime-kita/commit/95239005a589fb617ce21b42df96bde59aa16dd1))
* fix backup config ([b42cb65](https://github.com/syofyanzuhad/uptime-kita/commit/b42cb651e4e1a20ab37972c90fefeb14d6117bec))
* handle not found on public status page ([9628576](https://github.com/syofyanzuhad/uptime-kita/commit/9628576e6a8a8462a1321ae9766201ee078ff475))
* health prune ([0c8f662](https://github.com/syofyanzuhad/uptime-kita/commit/0c8f6629187bac16701bde749b416909911a4401))
* key features update ([794f94b](https://github.com/syofyanzuhad/uptime-kita/commit/794f94b0d5208f943a327b7f8bc8c1ef45be65ba))
* log uptime schedule ([b23ca65](https://github.com/syofyanzuhad/uptime-kita/commit/b23ca650a36276d97707c882d84d61abf585039e))
* Remove nightwatch and update dependencies ([d6d501f](https://github.com/syofyanzuhad/uptime-kita/commit/d6d501f0141191daa10cd6e2b22f0b15d833d0d9))
* rename Pages to pages ([2411ede](https://github.com/syofyanzuhad/uptime-kita/commit/2411ede37bc0a5fa0ffe39b01085a9ea60daad24))
* sqlite to nixpack ([0c8148a](https://github.com/syofyanzuhad/uptime-kita/commit/0c8148a4c60d88cdb9359dde49ee68021942ea6b))
* **style:** run pint ([5d5ee34](https://github.com/syofyanzuhad/uptime-kita/commit/5d5ee34aca89913084b1c16d9ddc5c5d89cc1624))
* Update .gitignore to include coverage directory ([ad0e7d4](https://github.com/syofyanzuhad/uptime-kita/commit/ad0e7d43e8579b88f48912396e581e3e1cac69bc))
* update app.blade.php to conditionally load analytics scripts based on environment ([789145c](https://github.com/syofyanzuhad/uptime-kita/commit/789145ccbdd492179571be00070bd68bc1b6590a))
* update FUNDING.yml and .DS_Store ([47aed3f](https://github.com/syofyanzuhad/uptime-kita/commit/47aed3fb20843abfeb2be82f5876b6b42be96bc0))
* update FUNDING.yml to include Open Collective support ([b0c7b0d](https://github.com/syofyanzuhad/uptime-kita/commit/b0c7b0d3c5b888ec51daa3556e53d36f9bf2e176))
* update README.md and .DS_Store ([27a331e](https://github.com/syofyanzuhad/uptime-kita/commit/27a331ea2c0a5f1d1cb0de3d1902000de9785e4d))
* update tests workflow to improve coverage reporting ([da1695e](https://github.com/syofyanzuhad/uptime-kita/commit/da1695e6b2702b6a9bd0b1d5552817f2e92a8998))
* update tests workflow to include coverage reporting and Codecov integration ([f1d59bd](https://github.com/syofyanzuhad/uptime-kita/commit/f1d59bd656edd19a491f533ba76458664711bf1d))
* update tests workflow to use coverage-clover format ([8190036](https://github.com/syofyanzuhad/uptime-kita/commit/8190036c1e7f1eea125223cd30e5bcb4da942f0d))
* Upgrade Laravel to v12.41.1 ([f5db874](https://github.com/syofyanzuhad/uptime-kita/commit/f5db87415f3d231e1e0e7d9354dd4743ccfee571))
* uptime concurrent check ([031f7b1](https://github.com/syofyanzuhad/uptime-kita/commit/031f7b171fa84b0568699a8b07fff44807295e92))


### Refactoring

* Monitor model history logging to ensure response time and status code are passed directly when available, rather than retrieved from the model. ([0bc92ca](https://github.com/syofyanzuhad/uptime-kita/commit/0bc92caf32a8f95f7cef0a545cde3a7bb9c675a1))
* **monitor-controllers:** clean up query formatting and improve code consistency ([64a1725](https://github.com/syofyanzuhad/uptime-kita/commit/64a172581c01771bcbf5f516c6245183867019d7))
* **monitor:** optimize database queries and improve performance with efficient indexing ([7b8e78b](https://github.com/syofyanzuhad/uptime-kita/commit/7b8e78b121250eeac92bc2bd6292e0291b85376b))
* **monitors:** replace inline footer with PublicFooter component in PublicIndex ([5b29379](https://github.com/syofyanzuhad/uptime-kita/commit/5b29379cf704cfa48178ec07bbd63eadc5961dbe))
* **monitors:** replace inline footer with PublicFooter component in PublicShow ([87a1d6b](https://github.com/syofyanzuhad/uptime-kita/commit/87a1d6bb449250aae47a4ec5ec2eafa6b3ec3332))
* **monitors:** replace inline footer with PublicFooter component in PublicShowNotFound ([1fafef0](https://github.com/syofyanzuhad/uptime-kita/commit/1fafef0843ba3b01329f68540eb6889a2459794e))
* **monitor:** streamline uptime record handling with updateOrInsert for improved efficiency ([fdb4505](https://github.com/syofyanzuhad/uptime-kita/commit/fdb450530abe96cd80d27474eb23bdbf62ff4d62))
* **monitors:** update PublicShow component to display monitor host instead of name ([0992ae9](https://github.com/syofyanzuhad/uptime-kita/commit/0992ae90c6709d7bc37b715e9ea1808cc0568abc))
* **monitor:** update current page handling in PublicIndex.vue ([8186a7b](https://github.com/syofyanzuhad/uptime-kita/commit/8186a7b91377f440e96c4445330ba37536891cf8))
* **monitor:** update tag structure in PublicIndex.vue for improved localization ([46cfd12](https://github.com/syofyanzuhad/uptime-kita/commit/46cfd12be4a433ced6615c29a00da89ac0cac90a))
* Move Recent Incidents below Uptime History in public monitor detail ([8909f33](https://github.com/syofyanzuhad/uptime-kita/commit/8909f33293dcf8d49f1b6295936d9b3ddd57dbdc))
* optimize daily uptime calculation job with chunk processing and delay to reduce database contention ([471dcf2](https://github.com/syofyanzuhad/uptime-kita/commit/471dcf255e30b5eea36b657ccdc1697fb52a6046))
* **pagination:** simplify pagination link handling in Index.vue and Pagination.vue ([60cfc4e](https://github.com/syofyanzuhad/uptime-kita/commit/60cfc4eb6a07d19867c11a4d5ba02409d2810136))
* **policy:** simplify monitor update, delete, restore, and forceDelete logic to allow admin access ([0a1b1f5](https://github.com/syofyanzuhad/uptime-kita/commit/0a1b1f51ab3cea98fb1ce0e02ddde5f4dea1b3a3))
* **PrivateMonitorController:** streamline monitor query logic ([cad6ee7](https://github.com/syofyanzuhad/uptime-kita/commit/cad6ee7c7df77bf07e91bcf4ea361be2c7f0db5b))
* **Public.vue:** optimize monitor fetching logic for authenticated users ([731367d](https://github.com/syofyanzuhad/uptime-kita/commit/731367d299f83901091b963b079bd03c9ff24873))
* **PublicIndex.vue, web.php:** update navigation and layout for public monitors ([a15cc15](https://github.com/syofyanzuhad/uptime-kita/commit/a15cc154c5ddb4618a1f045cad093c2cc04f4410))
* **PublicShow.vue:** enhance layout and responsiveness of monitor display ([97eb8ba](https://github.com/syofyanzuhad/uptime-kita/commit/97eb8ba0da98bab9eb75de61567322ff4714e80f))
* Rename recentIncidents to latestIncidents for clarity ([df4ce4f](https://github.com/syofyanzuhad/uptime-kita/commit/df4ce4ff15bf24229e438728588943ece1e78523))
* reorder imports and add additional health check commands to console schedule ([6363fa9](https://github.com/syofyanzuhad/uptime-kita/commit/6363fa911a21dc69368c47ffb6c6503d3ec18932))
* restructure health check routes with authentication and grouping ([6a447a4](https://github.com/syofyanzuhad/uptime-kita/commit/6a447a4ec01d6637ac8a18d415970c750ffb452f))
* **status-pages:** replace inline footer with PublicFooter component in Public ([7706f98](https://github.com/syofyanzuhad/uptime-kita/commit/7706f98fa752b5ae5924811b1e921c1b57e7913a))
* **ui:** improve button layout and restructure header in Show.vue for better alignment and spacing ([2beeb15](https://github.com/syofyanzuhad/uptime-kita/commit/2beeb15b8c02df3e5d507b679e574cdb75368423))
* **uptime:** remove refresh countdown logic and associated UI elements in Show.vue ([a326052](https://github.com/syofyanzuhad/uptime-kita/commit/a326052353bda3f5db700acc6e0acc3cfa2479d5))
* Use hostname only in MonitorStatusChanged notifications ([b9f73c8](https://github.com/syofyanzuhad/uptime-kita/commit/b9f73c804ee1886cf62e4c0ea12dbec09a2f58f1))
* **users:** streamline user query in index method ([670329d](https://github.com/syofyanzuhad/uptime-kita/commit/670329d1fc7521c694cbbfdeb9838ac4efc27819))


### Documentation

* Add documentation for new features ([9e88e06](https://github.com/syofyanzuhad/uptime-kita/commit/9e88e06b96049c1bc5b9d32b1601c3bacbef6e44))
* Update README with real-time SSE notification feature ([f868067](https://github.com/syofyanzuhad/uptime-kita/commit/f868067bd95e6e2b05644b206780bad0047beddd))
* uptime calculate daily ([275c143](https://github.com/syofyanzuhad/uptime-kita/commit/275c143d42a6d425156422b4536844743cdf2679))

# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.
