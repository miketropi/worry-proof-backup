import Notification from './Notification';

export default function BackupScheduleConfig() {
  return <div>
    <Notification type="warning" title="Backup Scheduling">
        <p className="tw-text-xs">
          <strong>Heads up! 🚨 </strong> This feature only works effectively when your website has stable traffic. If your data is too large it can take hours or even days for the process to complete. ⏰ 📊
        </p>
    </Notification>
  </div>;
}