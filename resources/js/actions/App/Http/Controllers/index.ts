import PublicMonitorController from './PublicMonitorController'
import PublicServerStatsController from './PublicServerStatsController'
import MonitorStatusStreamController from './MonitorStatusStreamController'
import StatisticMonitorController from './StatisticMonitorController'
import DashboardController from './DashboardController'
import PublicMonitorShowController from './PublicMonitorShowController'
import BadgeController from './BadgeController'
import OgImageController from './OgImageController'
import PublicStatusPageController from './PublicStatusPageController'
import LatestHistoryController from './LatestHistoryController'
import PinnedMonitorController from './PinnedMonitorController'
import MonitorListController from './MonitorListController'
import PrivateMonitorController from './PrivateMonitorController'
import MonitorImportController from './MonitorImportController'
import MonitorCompactController from './MonitorCompactController'
import UptimeMonitorController from './UptimeMonitorController'
import SubscribeMonitorController from './SubscribeMonitorController'
import UnsubscribeMonitorController from './UnsubscribeMonitorController'
import TagController from './TagController'
import ToggleMonitorActiveController from './ToggleMonitorActiveController'
import UptimesDailyController from './UptimesDailyController'
import StatusPageController from './StatusPageController'
import StatusPageAssociateMonitorController from './StatusPageAssociateMonitorController'
import StatusPageDisassociateMonitorController from './StatusPageDisassociateMonitorController'
import StatusPageAvailableMonitorsController from './StatusPageAvailableMonitorsController'
import StatusPageOrderController from './StatusPageOrderController'
import CustomDomainController from './CustomDomainController'
import UserController from './UserController'
import TestFlashController from './TestFlashController'
import DebugStatsController from './DebugStatsController'
import TelegramWebhookController from './TelegramWebhookController'
import Api from './Api'
import TelemetryDashboardController from './TelemetryDashboardController'
import Settings from './Settings'
import ServerResourceController from './ServerResourceController'
import NotificationController from './NotificationController'
import Auth from './Auth'

const Controllers = {
    PublicMonitorController: Object.assign(PublicMonitorController, PublicMonitorController),
    PublicServerStatsController: Object.assign(PublicServerStatsController, PublicServerStatsController),
    MonitorStatusStreamController: Object.assign(MonitorStatusStreamController, MonitorStatusStreamController),
    StatisticMonitorController: Object.assign(StatisticMonitorController, StatisticMonitorController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    PublicMonitorShowController: Object.assign(PublicMonitorShowController, PublicMonitorShowController),
    BadgeController: Object.assign(BadgeController, BadgeController),
    OgImageController: Object.assign(OgImageController, OgImageController),
    PublicStatusPageController: Object.assign(PublicStatusPageController, PublicStatusPageController),
    LatestHistoryController: Object.assign(LatestHistoryController, LatestHistoryController),
    PinnedMonitorController: Object.assign(PinnedMonitorController, PinnedMonitorController),
    MonitorListController: Object.assign(MonitorListController, MonitorListController),
    PrivateMonitorController: Object.assign(PrivateMonitorController, PrivateMonitorController),
    MonitorImportController: Object.assign(MonitorImportController, MonitorImportController),
    MonitorCompactController: Object.assign(MonitorCompactController, MonitorCompactController),
    UptimeMonitorController: Object.assign(UptimeMonitorController, UptimeMonitorController),
    SubscribeMonitorController: Object.assign(SubscribeMonitorController, SubscribeMonitorController),
    UnsubscribeMonitorController: Object.assign(UnsubscribeMonitorController, UnsubscribeMonitorController),
    TagController: Object.assign(TagController, TagController),
    ToggleMonitorActiveController: Object.assign(ToggleMonitorActiveController, ToggleMonitorActiveController),
    UptimesDailyController: Object.assign(UptimesDailyController, UptimesDailyController),
    StatusPageController: Object.assign(StatusPageController, StatusPageController),
    StatusPageAssociateMonitorController: Object.assign(StatusPageAssociateMonitorController, StatusPageAssociateMonitorController),
    StatusPageDisassociateMonitorController: Object.assign(StatusPageDisassociateMonitorController, StatusPageDisassociateMonitorController),
    StatusPageAvailableMonitorsController: Object.assign(StatusPageAvailableMonitorsController, StatusPageAvailableMonitorsController),
    StatusPageOrderController: Object.assign(StatusPageOrderController, StatusPageOrderController),
    CustomDomainController: Object.assign(CustomDomainController, CustomDomainController),
    UserController: Object.assign(UserController, UserController),
    TestFlashController: Object.assign(TestFlashController, TestFlashController),
    DebugStatsController: Object.assign(DebugStatsController, DebugStatsController),
    TelegramWebhookController: Object.assign(TelegramWebhookController, TelegramWebhookController),
    Api: Object.assign(Api, Api),
    TelemetryDashboardController: Object.assign(TelemetryDashboardController, TelemetryDashboardController),
    Settings: Object.assign(Settings, Settings),
    ServerResourceController: Object.assign(ServerResourceController, ServerResourceController),
    NotificationController: Object.assign(NotificationController, NotificationController),
    Auth: Object.assign(Auth, Auth),
}

export default Controllers