import BackupTable from './components/BackupTable';
import Footer from './components/Footer';
import ServerMetrics from './components/ServerMetrics';
import BackupProcess from './components/BackupProcess';
import { ConfirmProvider } from './components/Confirm';
import { ToastProvider } from './components/Toast';

const AppProvider = ({ children }) => {
  return (
    <ConfirmProvider>
      <ToastProvider>
        {children}
      </ToastProvider>
    </ConfirmProvider>
  );
};

export default function App() {
  return (
    <AppProvider>
      <BackupProcess />
      <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-8"> 
        <div className="md:tw-col-span-9">
          <BackupTable />
        </div>
        <div className="md:tw-col-span-3">
          <ServerMetrics metrics={wp_backup_php_data.server_metrics} />
        </div>
      </div>
      <Footer />
    </AppProvider>
  );
}