import CacheController from './CacheController';
import ClientRequestController from './ClientRequestController';
import CommandsController from './CommandsController';
import DumpController from './DumpController';
import EntriesController from './EntriesController';
import EventsController from './EventsController';
import ExceptionController from './ExceptionController';
import GatesController from './GatesController';
import HomeController from './HomeController';
import LogController from './LogController';
import MailController from './MailController';
import MailEmlController from './MailEmlController';
import MailHtmlController from './MailHtmlController';
import ModelsController from './ModelsController';
import MonitoredTagController from './MonitoredTagController';
import NotificationsController from './NotificationsController';
import QueriesController from './QueriesController';
import QueueBatchesController from './QueueBatchesController';
import QueueController from './QueueController';
import RecordingController from './RecordingController';
import RedisController from './RedisController';
import RequestsController from './RequestsController';
import ScheduleController from './ScheduleController';
import ViewsController from './ViewsController';

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
};

export default Controllers;
