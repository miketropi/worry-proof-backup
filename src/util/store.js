import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import { getBackups } from './lib';

const useBackupStore = create(
  immer((set, get) => ({
    // State
    backups: [],
    backupProcess: [],
    inProgress: false,
    inProgressStep: 0,
    restoreProcess: {
      process: [],
      inProgress: false,
      inProgressStep: 0,
    },

    // Actions
    setInProgress: (inProgress) =>
      set((state) => {
        state.inProgress = inProgress;
      }),

    setInProgressStep: (inProgressStep) =>
      set((state) => {
        state.inProgressStep = inProgressStep;
      }),

    setRestoreInProgress: (inProgress) =>
      set((state) => {
        state.restoreProcess.inProgress = inProgress;
      }),
    
    setRestoreInProgressStep: (inProgressStep) =>
      set((state) => {
        state.restoreProcess.inProgressStep = inProgressStep;
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
          description: '📝 Let\'s set the stage! Creating a shiny new config file with your backup preferences. Almost like writing a recipe for your perfect backup. 🍰',
          action: 'wp_backup_ajax_create_backup_config_file',
          payload: {
            name,
            types,
          },
        }
      ];
      
      const typeMessages = {
        database: `🗄️ Safeguarding your precious database records! 📊 Our data guardians are carefully preserving every table and relationship. Your information is in good hands! 🔒✨`,
        plugin: `🔌 Securing your powerful plugins! 🛠️ Each extension is being carefully wrapped and preserved. Your site's functionality is our top priority! 🚀💫`,
        theme: `🎨 Preserving your beautiful theme! 🎭 Every design element and customization is being carefully archived. Your site's look and feel is safe with us! 🎪✨`,
        uploads: `📁 Backing up your uploads folder! 🖼️ Every image, document, and media file is being carefully preserved. Your content is our treasure! 💎🌟`
      };

      // for each types, add a step to the process
      types.forEach((type) => {
        process.push({
          step: process.length + 1,
          name: `Generating ${type} backup`,
          description: typeMessages[type],
          action: `wp_backup_ajax_generate_backup_${type}`,
          payload: {
            name,
            type,
          },
        });
      });

      // add done step
      process.push({
        step: process.length + 1,
        name: 'Done',
        description: '🎉 All done! Your backup is complete and safe. Time to celebrate! 🥳',
        action: 'wp_backup_ajax_generate_backup_done',
        payload: {},
      });

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
    
    async fetchBackups_Fn() {
      try {
        const response = await getBackups();
        if (response.success == true) {
          set((state) => {
            state.backups = response.data;
          });
        }
      } catch (error) {
        console.error('Error fetching backups:', error);
      }
    },

    buildRestoreProcess: (backup) => {
      const { folder_name } = backup;
      let types = [...backup.types];
      const typeMessages = {
        database: `🗄️ Restoring your precious database records! 📊 Our data wizards are carefully bringing back every table and relationship. Your information is coming back to life! 🔄✨`,
        plugin: `🔌 Restoring your powerful plugins! 🛠️ Each extension is being carefully unwrapped and brought back online. Your site's functionality is getting its groove back! 🚀💫`,
        theme: `🎨 Restoring your beautiful theme! 🎭 Every design element and customization is being carefully revived. Your site's look and feel is getting its glow back! 🎪✨`,
        uploads: `📁 Restoring your uploads folder! 🖼️ Every image, document, and media file is being carefully brought back. Your content is getting its sparkle back! 💎🌟`
      };
      
      const process = [
        {
          step: 1,
          name: 'Read Config File',
          description: '📖 Oop, let me check what you wanted restored! Reading your backup config like a pro detective 🔍✨',
          action: 'wp_backup_ajax_restore_read_backup_config_file',
          payload: {
            folder_name,
            types: types.join(','),
          },
        }
      ];

      // check types include database, and move it to the end of the array
      if (types.includes('database')) {
        types.push(types.splice(types.indexOf('database'), 1)[0]);
        console.log('types', types);
      }

      // for each type, add a step to the process
      types.forEach((type) => {
        process.push({
          step: process.length + 1,
          name: `Restoring ${type}`,
          description: typeMessages[type],
          action: `wp_backup_ajax_restore_${type}`,
          payload: {
            folder_name,
            type, 
          },
        });
      });

      // add done step
      process.push({
        step: process.length + 1,
        name: 'Done',
        description: '🎉 All done! Your restore is complete and everything is back to normal. Time to celebrate! 🥳',
        action: 'wp_backup_ajax_restore_done',
        payload: {
          folder_name,
        },
      });
      console.log('process', process);

      set((state) => {
        state.restoreProcess.process = process;
        // set inProgress to true
        state.restoreProcess.inProgress = true;
        // set inProgressStep to 1
        state.restoreProcess.inProgressStep = 1;
      });
    }
  }))
);

export default useBackupStore;
