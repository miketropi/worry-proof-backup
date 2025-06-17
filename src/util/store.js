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
          description: 'Create a config file containing the backup configuration.',
          action: 'generate_config_file',
          payload: {
            name,
            types,
          },
        },
        {
          step: 2,
          name: 'Generate Backup',
          description: `Generate the backup for selected types: ${types.join(', ')}`,
          action: 'generate_backup',
          payload: {
            types,
          },
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
