import FrontendAssets from './FrontendAssets';
import HandleRequests from './HandleRequests';

const Mechanisms = {
    HandleRequests: Object.assign(HandleRequests, HandleRequests),
    FrontendAssets: Object.assign(FrontendAssets, FrontendAssets),
};

export default Mechanisms;
