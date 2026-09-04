import HandleRequests from './HandleRequests'
import FrontendAssets from './FrontendAssets'

const Mechanisms = {
    HandleRequests: Object.assign(HandleRequests, HandleRequests),
    FrontendAssets: Object.assign(FrontendAssets, FrontendAssets),
}

export default Mechanisms