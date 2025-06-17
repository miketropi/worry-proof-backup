import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

const useBackupStore = create(
  immer((set, get) => ({
    // State
    backups: [],
    backupProcess: [],
    inProgress: false,
    inProgressStep: 0,

    // Actions
    setInProgress: (inProgress) =>
      set((state) => {
        state.inProgress = inProgress;
      }),

    setInProgressStep: (inProgressStep) =>
      set((state) => {
        state.inProgressStep = inProgressStep;
      }),

    setBackups: (backups) =>
      set((state) => {
        state.backups = backups;
      }),

    deleteBackupById: (backupId) =>
      set((state) => {
        state.backups = state.backups.filter((backup) => backup.id !== backupId);
      }),
    
    buildBackupProcess: (backupConfig) => {
      const { name, types } = backupConfig;
      // backup process had 2 steps: 1. generate config file containing the backup config, 2. generate the backup with types (database, plugins, themes, uploads)
      const process = [
        {
          step: 1,
          name: 'Generate Config File',
          description: '📝 Let’s set the stage! Creating a shiny new config file with your backup preferences. Almost like writing a recipe for your perfect backup. 🍰',
          action: 'wp_backup_ajax_create_backup_config_file',
          payload: {
            name,
            types,
          },
        },
        {
          step: 2,
          name: 'Generate Backup',
          description: `🪄✨ The vault opens! Initiating backup for your chosen treasures: ${types.map(type => `«${type}»`).join(', ')}. Our digital guardians are on watch—your data is about to be wrapped in a cloak of safety and stardust. 🚀🔒`,
          action: 'generate_backup',
          payload: {
            types,
          },
        },
        // step 3 complete
        {
          step: 3,
          name: 'Done',
          description: '🎉 All done! Your backup is complete and safe. Time to celebrate! 🥳',
          action: 'done',
          payload: {},
        },
      ];
      set((state) => {
        state.backupProcess = process;
      });

      // set inProgress to true
      set((state) => {
        state.inProgress = true;
      });

      // set step to 1
      set((state) => {
        state.inProgressStep = 1;
      });
    },
    
  }))
);

export default useBackupStore;
