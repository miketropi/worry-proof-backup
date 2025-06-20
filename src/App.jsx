import BackupTable from './components/BackupTable';
import Footer from './components/Footer';
import ServerMetrics from './components/ServerMetrics';
import BackupProcess from './components/BackupProcess';
import { ConfirmProvider } from './components/Confirm';
import { ToastProvider } from './components/Toast';
import Tab from './components/Tab';
import { Package, Book, MessageCircle } from 'lucide-react';

const AppProvider = ({ children }) => {
  return (
    <ConfirmProvider>
      <ToastProvider>
        {children}
      </ToastProvider>
    </ConfirmProvider>
  );
};

const tabs = [
  {
    label: 'Your Backups',
    icon: <Package size={14} />,
    content: <>
      <BackupProcess />
      <BackupTable />
    </>
  },
  {
    label: 'Documentation',
    icon: <Book size={14} />,
    content: <div className="tw-text-center tw-py-12">
      <div className="tw-text-6xl tw-mb-4">🚀</div>
      <h3 className="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">
        Documentation is cooking! 👨‍🍳
      </h3>
      <p className="tw-text-gray-600 tw-mb-4">
        We're whipping up some amazing docs for you! 📚✨
      </p>
      <div className="tw-text-sm tw-text-gray-500">
        Stay tuned for the full guide on how to master your backups! 💪
      </div>
    </div>
  },
  // feedback
  {
    label: 'Feedback',
    icon: <MessageCircle size={14} />,
    content: <div className="tw-text-center tw-py-12">
      <div className="tw-text-6xl tw-mb-4">💬✨</div>
      <h3 className="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">
        Spill the tea! ☕️
      </h3>
      <p className="tw-text-gray-600 tw-mb-4">
        We're all ears for your thoughts! Drop us some feedback and let's make this backup thing absolutely fire! 🔥
      </p>
      <div className="tw-text-sm tw-text-gray-500">
        Your feedback helps us level up this plugin to the next level! 🚀💪
      </div>
    </div>
  },
];

export default function App() {
  return (
    <AppProvider>
      
      <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-8"> 
        <div className="md:tw-col-span-9">
          <Tab tabs={tabs} />
          {/* <BackupTable /> */}
        </div>
        <div className="md:tw-col-span-3">
          <ServerMetrics metrics={wp_backup_php_data.server_metrics} />
        </div>
      </div>
      <Footer />
    </AppProvider>
  );
}