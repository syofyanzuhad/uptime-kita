import FileUploadController from './FileUploadController'
import FilePreviewController from './FilePreviewController'

const SupportFileUploads = {
    FileUploadController: Object.assign(FileUploadController, FileUploadController),
    FilePreviewController: Object.assign(FilePreviewController, FilePreviewController),
}

export default SupportFileUploads