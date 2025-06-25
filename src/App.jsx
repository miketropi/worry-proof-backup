// import BackupTable from './components/BackupTable';
import Footer from './components/Footer';
import ServerMetrics from './components/ServerMetrics';
import BackupProcess from './components/BackupProcess';
import { ConfirmProvider } from './components/Confirm';
import { ToastProvider } from './components/Toast';
import Tab from './components/Tab';
import { Package, Book, Bug, Heart } from 'lucide-react';
import { ModalProvider } from './components/Modal';
import BackupTable from './components/BackupTable.refactored';
import RestoreProcess from './components/RestoreProcess';
import DonationInfomation from './components/DonationInfomation';

const AppProvider = ({ children }) => {
  return (
    <ConfirmProvider>
      <ToastProvider>
        <ModalProvider>
          {children}
        </ModalProvider>
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
      <RestoreProcess />
      <BackupTable />
    </>
  },
  // {
  //   label: 'Documentation',
  //   icon: <Book size={14} />,
  //   content: <div className="tw-text-center tw-py-12">
  //     <div className="tw-text-6xl tw-mb-4">🚀</div>
  //     <h3 className="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">
  //       Documentation is cooking! 👨‍🍳
  //     </h3>
  //     <p className="tw-text-gray-600 tw-mb-4">
  //       We're whipping up some amazing docs for you! 📚✨
  //     </p>
  //     <div className="tw-text-sm tw-text-gray-500">
  //       Stay tuned for the full guide on how to master your backups! 💪
  //     </div>
  //   </div>
  // },
  // report issue
  {
    label: 'Report Issue',
    icon: <Bug size={14} />,
    content: <div className="tw-text-center tw-py-12">
      <div className="tw-text-6xl tw-mb-4">🐛</div>
      <h3 className="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">
        Found a bug? Let's squash it! 🥾
      </h3>
      <p className="tw-text-gray-600 tw-mb-6">
        Help us make WP Backup even better by reporting any issues you encounter.
      </p>
      
      <div className="tw-max-w-6xl tw-mx-auto">
        <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
          <div className="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4">
            <div className="tw-flex tw-items-center tw-gap-2 tw-mb-2 tw-justify-center">
              <span className="tw-text-blue-600">📧</span>
              <span className="tw-font-medium tw-text-blue-900">Email Support</span>
            </div>
            <p className="tw-text-sm tw-text-blue-700">
              Send us a detailed email with screenshots and steps to reproduce.
            </p>
          </div>
          
          <div className="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4">
            <div className="tw-flex tw-items-center tw-gap-2 tw-mb-2 tw-justify-center">
              <span className="tw-text-green-600">🔗</span>
              <span className="tw-font-medium tw-text-green-900">GitHub Issues</span>
            </div>
            <p className="tw-text-sm tw-text-green-700">
              Create an issue on our GitHub repository for public tracking.
            </p>
          </div>
          
          <div className="tw-bg-purple-50 tw-border tw-border-purple-200 tw-rounded-lg tw-p-4">
            <div className="tw-flex tw-items-center tw-gap-2 tw-mb-2 tw-justify-center">
              <span className="tw-text-purple-600">💬</span>
              <span className="tw-font-medium tw-text-purple-900">Community Forum</span>
            </div>
            <p className="tw-text-sm tw-text-purple-700">
              Join our community discussions and get help from other users.
            </p>
          </div>
        </div>
      </div>
      
      <div className="tw-mt-8 tw-text-sm tw-text-gray-500">
        <p>💡 <strong>Pro tip:</strong> Include your WordPress version, PHP version, and plugin version for faster resolution!</p>
      </div>
    </div>
  },
  {
    label: 'Support the author',
    icon: <Heart size={14} />,
    content: <DonationInfomation />
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