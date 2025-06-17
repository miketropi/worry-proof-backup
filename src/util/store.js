import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

const useBackupStore = create(
  immer((set) => ({
    // State
    backups: [],

    // Actions
    setBackups: (backups) =>
      set((state) => {
        state.backups = backups;
      }),

    deleteBackupById: (backupId) =>
      set((state) => {
        state.backups = state.backups.filter((backup) => backup.id !== backupId);
      }),
  }))
);

export default useBackupStore;
