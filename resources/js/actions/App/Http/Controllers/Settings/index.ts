import ProfileController from './ProfileController'
import PasswordController from './PasswordController'
import AppearanceController from './AppearanceController'
import DatabaseBackupController from './DatabaseBackupController'
import TelemetryController from './TelemetryController'

const Settings = {
    ProfileController: Object.assign(ProfileController, ProfileController),
    PasswordController: Object.assign(PasswordController, PasswordController),
    AppearanceController: Object.assign(AppearanceController, AppearanceController),
    DatabaseBackupController: Object.assign(DatabaseBackupController, DatabaseBackupController),
    TelemetryController: Object.assign(TelemetryController, TelemetryController),
}

export default Settings