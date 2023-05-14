import { library, dom} from '@fortawesome/fontawesome-svg-core'
import { faPhone, faWrench, faHouse, faOtter, faFileSignature, faHandshake, faHome, faBuilding, faSun, faPaperPlane} from '@fortawesome/free-solid-svg-icons'

/* add icons to the library */
library.add(faPhone, faHouse, faWrench, faOtter, faFileSignature, faHandshake, faHome, faBuilding, faSun, faPaperPlane)
dom.watch()

export default library