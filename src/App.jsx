import BackupTable from './components/BackupTable';
import Footer from './components/Footer';
import ServerMetrics from './components/ServerMetrics';

export default function App() {
  return (
    <div>
      <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
        <div className="md:col-span-9">
          <BackupTable />
        </div>
        <div className="md:col-span-3">
          <ServerMetrics metrics={wp_backup_php_data.server_metrics} />
        </div>
      </div>
      <Footer />
    </div>
  );
}