import Api from './Api';
import Auth from './Auth';
import BadgeController from './BadgeController';
import CustomDomainController from './CustomDomainController';
import DashboardController from './DashboardController';
import DebugStatsController from './DebugStatsController';
import LatestHistoryController from './LatestHistoryController';
import MonitorCompactController from './MonitorCompactController';
import MonitorImportController from './MonitorImportController';
import MonitorListController from './MonitorListController';
import MonitorStatusStreamController from './MonitorStatusStreamController';
import NotificationController from './NotificationController';
import OgImageController from './OgImageController';
import PinnedMonitorController from './PinnedMonitorController';
import PrivateMonitorController from './PrivateMonitorController';
import PublicMonitorController from './PublicMonitorController';
import PublicMonitorShowController from './PublicMonitorShowController';
import PublicServerStatsController from './PublicServerStatsController';
import PublicStatusPageController from './PublicStatusPageController';
import ServerResourceController from './ServerResourceController';
import Settings from './Settings';
import StatisticMonitorController from './StatisticMonitorController';
import StatusPageAssociateMonitorController from './StatusPageAssociateMonitorController';
import StatusPageAvailableMonitorsController from './StatusPageAvailableMonitorsController';
import StatusPageController from './StatusPageController';
import StatusPageDisassociateMonitorController from './StatusPageDisassociateMonitorController';
import StatusPageOrderController from './StatusPageOrderController';
import SubscribeMonitorController from './SubscribeMonitorController';
import TagController from './TagController';
import TelegramWebhookController from './TelegramWebhookController';
import TelemetryDashboardController from './TelemetryDashboardController';
import TestFlashController from './TestFlashController';
import ToggleMonitorActiveController from './ToggleMonitorActiveController';
import UnsubscribeMonitorController from './UnsubscribeMonitorController';
import UptimeMonitorController from './UptimeMonitorController';
import UptimesDailyController from './UptimesDailyController';
import UserController from './UserController';

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
    MonitorCompactController: Object.assign(MonitorCompactController, MonitorCompactController),
    PinnedMonitorController: Object.assign(PinnedMonitorController, PinnedMonitorController),
    MonitorListController: Object.assign(MonitorListController, MonitorListController),
    PrivateMonitorController: Object.assign(PrivateMonitorController, PrivateMonitorController),
    MonitorImportController: Object.assign(MonitorImportController, MonitorImportController),
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
};

export default Controllers;
