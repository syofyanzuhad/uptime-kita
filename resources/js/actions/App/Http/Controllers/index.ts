import Api from './Api';
import Auth from './Auth';
import BadgeController from './BadgeController';
import CustomDomainController from './CustomDomainController';
import DashboardController from './DashboardController';
import DebugStatsController from './DebugStatsController';
import DomainCheckController from './DomainCheckController';
import LatestHistoryController from './LatestHistoryController';
import MonitorCompactController from './MonitorCompactController';
import MonitorExpirationController from './MonitorExpirationController';
import MonitorExportController from './MonitorExportController';
import MonitorHistoryController from './MonitorHistoryController';
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
import PublicToolsController from './PublicToolsController';
import ServerResourceController from './ServerResourceController';
import Settings from './Settings';
import StatisticMonitorController from './StatisticMonitorController';
import StatusPageAssociateMonitorController from './StatusPageAssociateMonitorController';
import StatusPageAvailableMonitorsController from './StatusPageAvailableMonitorsController';
import StatusPageController from './StatusPageController';
import StatusPageDisassociateMonitorController from './StatusPageDisassociateMonitorController';
import StatusPageOrderController from './StatusPageOrderController';
import SubscribeMonitorController from './SubscribeMonitorController';
import SubscribeStatusPageController from './SubscribeStatusPageController';
import TagController from './TagController';
import TelegramWebhookController from './TelegramWebhookController';
import TelemetryDashboardController from './TelemetryDashboardController';
import TestFlashController from './TestFlashController';
import ToggleMonitorActiveController from './ToggleMonitorActiveController';
import ToggleNotificationChannelController from './ToggleNotificationChannelController';
import UnsubscribeMonitorController from './UnsubscribeMonitorController';
import UnsubscribeStatusPageController from './UnsubscribeStatusPageController';
import UptimeMonitorController from './UptimeMonitorController';
import UptimesDailyController from './UptimesDailyController';
import UserController from './UserController';
import VerifyStatusPageSubscriptionController from './VerifyStatusPageSubscriptionController';

const Controllers = {
    Api: Object.assign(Api, Api),
    PublicMonitorController: Object.assign(PublicMonitorController, PublicMonitorController),
    PublicToolsController: Object.assign(PublicToolsController, PublicToolsController),
    DomainCheckController: Object.assign(DomainCheckController, DomainCheckController),
    PublicServerStatsController: Object.assign(PublicServerStatsController, PublicServerStatsController),
    MonitorStatusStreamController: Object.assign(MonitorStatusStreamController, MonitorStatusStreamController),
    StatisticMonitorController: Object.assign(StatisticMonitorController, StatisticMonitorController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    PublicMonitorShowController: Object.assign(PublicMonitorShowController, PublicMonitorShowController),
    BadgeController: Object.assign(BadgeController, BadgeController),
    OgImageController: Object.assign(OgImageController, OgImageController),
    PublicStatusPageController: Object.assign(PublicStatusPageController, PublicStatusPageController),
    SubscribeStatusPageController: Object.assign(SubscribeStatusPageController, SubscribeStatusPageController),
    VerifyStatusPageSubscriptionController: Object.assign(VerifyStatusPageSubscriptionController, VerifyStatusPageSubscriptionController),
    UnsubscribeStatusPageController: Object.assign(UnsubscribeStatusPageController, UnsubscribeStatusPageController),
    LatestHistoryController: Object.assign(LatestHistoryController, LatestHistoryController),
    MonitorCompactController: Object.assign(MonitorCompactController, MonitorCompactController),
    PinnedMonitorController: Object.assign(PinnedMonitorController, PinnedMonitorController),
    MonitorExpirationController: Object.assign(MonitorExpirationController, MonitorExpirationController),
    MonitorListController: Object.assign(MonitorListController, MonitorListController),
    PrivateMonitorController: Object.assign(PrivateMonitorController, PrivateMonitorController),
    MonitorImportController: Object.assign(MonitorImportController, MonitorImportController),
    MonitorExportController: Object.assign(MonitorExportController, MonitorExportController),
    UptimeMonitorController: Object.assign(UptimeMonitorController, UptimeMonitorController),
    SubscribeMonitorController: Object.assign(SubscribeMonitorController, SubscribeMonitorController),
    UnsubscribeMonitorController: Object.assign(UnsubscribeMonitorController, UnsubscribeMonitorController),
    TagController: Object.assign(TagController, TagController),
    ToggleMonitorActiveController: Object.assign(ToggleMonitorActiveController, ToggleMonitorActiveController),
    MonitorHistoryController: Object.assign(MonitorHistoryController, MonitorHistoryController),
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
    TelemetryDashboardController: Object.assign(TelemetryDashboardController, TelemetryDashboardController),
    Settings: Object.assign(Settings, Settings),
    ServerResourceController: Object.assign(ServerResourceController, ServerResourceController),
    NotificationController: Object.assign(NotificationController, NotificationController),
    ToggleNotificationChannelController: Object.assign(ToggleNotificationChannelController, ToggleNotificationChannelController),
    Auth: Object.assign(Auth, Auth),
};

export default Controllers;
