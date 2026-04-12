import MailController from './MailController'
import MailHtmlController from './MailHtmlController'
import MailEmlController from './MailEmlController'
import ExceptionController from './ExceptionController'
import DumpController from './DumpController'
import LogController from './LogController'
import NotificationsController from './NotificationsController'
import QueueController from './QueueController'
import QueueBatchesController from './QueueBatchesController'
import EventsController from './EventsController'
import GatesController from './GatesController'
import CacheController from './CacheController'
import QueriesController from './QueriesController'
import ModelsController from './ModelsController'
import RequestsController from './RequestsController'
import ViewsController from './ViewsController'
import CommandsController from './CommandsController'
import ScheduleController from './ScheduleController'
import RedisController from './RedisController'
import ClientRequestController from './ClientRequestController'
import MonitoredTagController from './MonitoredTagController'
import RecordingController from './RecordingController'
import EntriesController from './EntriesController'
import HomeController from './HomeController'

const Controllers = {
    MailController: Object.assign(MailController, MailController),
    MailHtmlController: Object.assign(MailHtmlController, MailHtmlController),
    MailEmlController: Object.assign(MailEmlController, MailEmlController),
    ExceptionController: Object.assign(ExceptionController, ExceptionController),
    DumpController: Object.assign(DumpController, DumpController),
    LogController: Object.assign(LogController, LogController),
    NotificationsController: Object.assign(NotificationsController, NotificationsController),
    QueueController: Object.assign(QueueController, QueueController),
    QueueBatchesController: Object.assign(QueueBatchesController, QueueBatchesController),
    EventsController: Object.assign(EventsController, EventsController),
    GatesController: Object.assign(GatesController, GatesController),
    CacheController: Object.assign(CacheController, CacheController),
    QueriesController: Object.assign(QueriesController, QueriesController),
    ModelsController: Object.assign(ModelsController, ModelsController),
    RequestsController: Object.assign(RequestsController, RequestsController),
    ViewsController: Object.assign(ViewsController, ViewsController),
    CommandsController: Object.assign(CommandsController, CommandsController),
    ScheduleController: Object.assign(ScheduleController, ScheduleController),
    RedisController: Object.assign(RedisController, RedisController),
    ClientRequestController: Object.assign(ClientRequestController, ClientRequestController),
    MonitoredTagController: Object.assign(MonitoredTagController, MonitoredTagController),
    RecordingController: Object.assign(RecordingController, RecordingController),
    EntriesController: Object.assign(EntriesController, EntriesController),
    HomeController: Object.assign(HomeController, HomeController),
}

export default Controllers