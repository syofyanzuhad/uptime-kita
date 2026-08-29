import SimpleHealthCheckController from './SimpleHealthCheckController'
import HealthCheckJsonResultsController from './HealthCheckJsonResultsController'
import HealthCheckResultsController from './HealthCheckResultsController'

const Controllers = {
    SimpleHealthCheckController: Object.assign(SimpleHealthCheckController, SimpleHealthCheckController),
    HealthCheckJsonResultsController: Object.assign(HealthCheckJsonResultsController, HealthCheckJsonResultsController),
    HealthCheckResultsController: Object.assign(HealthCheckResultsController, HealthCheckResultsController),
}

export default Controllers