import AuthenticatedSessionController from './AuthenticatedSessionController';
import ConfirmablePasswordController from './ConfirmablePasswordController';
import EmailVerificationNotificationController from './EmailVerificationNotificationController';
import EmailVerificationPromptController from './EmailVerificationPromptController';
import NewPasswordController from './NewPasswordController';
import PasswordResetLinkController from './PasswordResetLinkController';
import RegisteredUserController from './RegisteredUserController';
import SocialiteController from './SocialiteController';
import VerifyEmailController from './VerifyEmailController';

const Auth = {
    RegisteredUserController: Object.assign(RegisteredUserController, RegisteredUserController),
    AuthenticatedSessionController: Object.assign(AuthenticatedSessionController, AuthenticatedSessionController),
    PasswordResetLinkController: Object.assign(PasswordResetLinkController, PasswordResetLinkController),
    NewPasswordController: Object.assign(NewPasswordController, NewPasswordController),
    SocialiteController: Object.assign(SocialiteController, SocialiteController),
    EmailVerificationPromptController: Object.assign(EmailVerificationPromptController, EmailVerificationPromptController),
    VerifyEmailController: Object.assign(VerifyEmailController, VerifyEmailController),
    EmailVerificationNotificationController: Object.assign(EmailVerificationNotificationController, EmailVerificationNotificationController),
    ConfirmablePasswordController: Object.assign(ConfirmablePasswordController, ConfirmablePasswordController),
};

export default Auth;
