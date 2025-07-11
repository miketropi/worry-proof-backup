// import BackupTable from './components/BackupTable';
import Footer from './components/Footer';
import ServerMetrics from './components/ServerMetrics';
import BackupProcess from './components/BackupProcess';
import { ConfirmProvider } from './components/Confirm';
import { ToastProvider } from './components/Toast';
import Tab from './components/Tab';
import { Package, Book, Bug, Heart, Server } from 'lucide-react';
import { ModalProvider } from './components/Modal';
import BackupTable from './components/BackupTable.refactored';
import RestoreProcess from './components/RestoreProcess';
import DonationInfomation from './components/DonationInfomation';
import ReportIssue from './components/ReportIssue';

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
  // server metrics
  {
    label: 'Server Metrics',
    icon: <Server size={14} />,
    content: <ServerMetrics metrics={worrpb_php_data.server_metrics} />
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
    content: <ReportIssue />
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
      <div className="tw-w-full">
        <Tab tabs={tabs} />
        {/* <BackupTable /> */}
      </div>
      <Footer />
    </AppProvider>
  );
}