import Injector from 'lib/Injector';
import SilvervaultFileField from '../components/SilvervaultFileField/SilvervaultFileField';

export default () => {
  Injector.component.registerMany({
    SilvervaultFileField,
  });
};
