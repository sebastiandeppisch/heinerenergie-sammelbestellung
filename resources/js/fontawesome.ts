import { library, dom} from '@fortawesome/fontawesome-svg-core'
import { faPhone, faWrench, faHouse, faOtter, faFileSignature, faHandshake, faHome, faBuilding, faSun, faPaperPlane, faCheckCircle} from '@fortawesome/free-solid-svg-icons'

/* add icons to the library */
library.add(faPhone, faHouse, faWrench, faOtter, faFileSignature, faHandshake, faHome, faBuilding, faSun, faPaperPlane, faCheckCircle)
dom.watch()

export default library