import HostsController from './HostsController'
import FoldersController from './FoldersController'
import FilesController from './FilesController'
import LogsController from './LogsController'
import IndexController from './IndexController'

const Controllers = {
    HostsController: Object.assign(HostsController, HostsController),
    FoldersController: Object.assign(FoldersController, FoldersController),
    FilesController: Object.assign(FilesController, FilesController),
    LogsController: Object.assign(LogsController, LogsController),
    IndexController: Object.assign(IndexController, IndexController),
}

export default Controllers