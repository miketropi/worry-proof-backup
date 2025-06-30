import Notification from './Notification';

export default function BackupScheduleConfig() {
  return <div>
    <Notification type="info" title="WordPress Cron & Backup Scheduling">
        <p className="tw-text-xs">
          We use <strong>WordPress cron</strong> to schedule automatic backups. This is WordPress's built-in 
          scheduling system that manages when your backups run.
        </p>
        <p className="tw-text-xs">
          <strong>How WordPress cron works:</strong> Unlike traditional server cron jobs, WordPress cron is 
          "virtual" - it only runs when someone visits your site. When a visitor loads a page, WordPress 
          checks if any scheduled tasks (like backups) are due and executes them.
        </p>
        <p className="tw-text-xs">
          <strong>Potential risks:</strong>
        </p>
        <ul className="tw-list-disc tw-list-inside tw-ml-2 tw-space-y-1 tw-text-xs">
          <li>Backups may be delayed if your site has low traffic</li>
          <li>Long-running backups could timeout if visitors leave the page</li>
          <li>Server resources may be limited during peak traffic times</li>
        </ul>
    </Notification>
  </div>;
}