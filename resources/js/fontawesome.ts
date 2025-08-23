import { dom, library } from '@fortawesome/fontawesome-svg-core';
import {
    faBuilding,
    faCheckCircle,
    faFileSignature,
    faHandshake,
    faHome,
    faHouse,
    faOtter,
    faPaperPlane,
    faPhone,
    faPlus,
    faSun,
    faWrench,
} from '@fortawesome/free-solid-svg-icons';

/* add icons to the library */
library.add(faPhone, faHouse, faWrench, faOtter, faFileSignature, faHandshake, faHome, faBuilding, faSun, faPaperPlane, faCheckCircle, faPlus);
dom.watch();

export default library;
