import FilePreviewController from './FilePreviewController';
import FileUploadController from './FileUploadController';

const SupportFileUploads = {
    FileUploadController: Object.assign(FileUploadController, FileUploadController),
    FilePreviewController: Object.assign(FilePreviewController, FilePreviewController),
};

export default SupportFileUploads;
