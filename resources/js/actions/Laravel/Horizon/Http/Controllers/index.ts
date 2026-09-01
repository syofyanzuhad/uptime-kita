import BatchesController from './BatchesController';
import CompletedJobsController from './CompletedJobsController';
import DashboardStatsController from './DashboardStatsController';
import FailedJobsController from './FailedJobsController';
import HomeController from './HomeController';
import JobMetricsController from './JobMetricsController';
import JobsController from './JobsController';
import MasterSupervisorController from './MasterSupervisorController';
import MonitoringController from './MonitoringController';
import PendingJobsController from './PendingJobsController';
import QueueMetricsController from './QueueMetricsController';
import RetryController from './RetryController';
import SilencedJobsController from './SilencedJobsController';
import WorkloadController from './WorkloadController';

const Controllers = {
    DashboardStatsController: Object.assign(DashboardStatsController, DashboardStatsController),
    WorkloadController: Object.assign(WorkloadController, WorkloadController),
    MasterSupervisorController: Object.assign(MasterSupervisorController, MasterSupervisorController),
    MonitoringController: Object.assign(MonitoringController, MonitoringController),
    JobMetricsController: Object.assign(JobMetricsController, JobMetricsController),
    QueueMetricsController: Object.assign(QueueMetricsController, QueueMetricsController),
    BatchesController: Object.assign(BatchesController, BatchesController),
    PendingJobsController: Object.assign(PendingJobsController, PendingJobsController),
    CompletedJobsController: Object.assign(CompletedJobsController, CompletedJobsController),
    SilencedJobsController: Object.assign(SilencedJobsController, SilencedJobsController),
    FailedJobsController: Object.assign(FailedJobsController, FailedJobsController),
    RetryController: Object.assign(RetryController, RetryController),
    JobsController: Object.assign(JobsController, JobsController),
    HomeController: Object.assign(HomeController, HomeController),
};

export default Controllers;
