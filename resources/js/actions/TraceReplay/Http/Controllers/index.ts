import DashboardController from './DashboardController'
import Api from './Api'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    Api: Object.assign(Api, Api),
}

export default Controllers