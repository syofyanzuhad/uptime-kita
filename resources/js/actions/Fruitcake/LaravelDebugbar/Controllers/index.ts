import OpenHandlerController from './OpenHandlerController'
import CacheController from './CacheController'
import QueriesController from './QueriesController'
import AssetController from './AssetController'
import TelescopeController from './TelescopeController'

const Controllers = {
    OpenHandlerController: Object.assign(OpenHandlerController, OpenHandlerController),
    CacheController: Object.assign(CacheController, CacheController),
    QueriesController: Object.assign(QueriesController, QueriesController),
    AssetController: Object.assign(AssetController, AssetController),
    TelescopeController: Object.assign(TelescopeController, TelescopeController),
}

export default Controllers