import AssetController from './AssetController';
import CacheController from './CacheController';
import OpenHandlerController from './OpenHandlerController';
import QueriesController from './QueriesController';
import TelescopeController from './TelescopeController';

const Controllers = {
    OpenHandlerController: Object.assign(OpenHandlerController, OpenHandlerController),
    CacheController: Object.assign(CacheController, CacheController),
    QueriesController: Object.assign(QueriesController, QueriesController),
    AssetController: Object.assign(AssetController, AssetController),
    TelescopeController: Object.assign(TelescopeController, TelescopeController),
};

export default Controllers;
