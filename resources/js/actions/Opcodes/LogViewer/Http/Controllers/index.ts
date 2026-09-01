import FilesController from './FilesController';
import FoldersController from './FoldersController';
import HostsController from './HostsController';
import IndexController from './IndexController';
import LogsController from './LogsController';

const Controllers = {
    HostsController: Object.assign(HostsController, HostsController),
    FoldersController: Object.assign(FoldersController, FoldersController),
    FilesController: Object.assign(FilesController, FilesController),
    LogsController: Object.assign(LogsController, LogsController),
    IndexController: Object.assign(IndexController, IndexController),
};

export default Controllers;
