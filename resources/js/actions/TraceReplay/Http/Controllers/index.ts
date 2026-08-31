import Api from './Api';
import DashboardController from './DashboardController';

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    Api: Object.assign(Api, Api),
};

export default Controllers;
