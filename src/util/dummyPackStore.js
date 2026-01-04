import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

const useDummyPackStore = create(
	immer((set, get) => ({
		// State
    installProcess: {
			process: [],
			inProgress: false,
			inProgressStep: 0,
      isModalOpen: false,
      packData: null,
		},
    buildInstallProcess: (packData) => {
      const { ID, size } = packData;
      const process = [
				{
					step: 1,
					name: `Download Package ${ size ? `(${size})` : '' }`,
					description: '⬇️ Downloading the dummy pack package. Please wait while we fetch the files for you!',
					action: 'worrprba_ajax_download_dummy_pack',
					payload: {
            ID
          },
				},
				{
					step: 2,
					name: 'Unzip Package',
					description: '🗃️ Unzipping the downloaded package to prepare for installation!',
					action: 'worrprba_ajax_unzip_dummy_pack',
					payload: {},
				},
				{
					step: 3,
					name: 'Restore Database',
					description: '🗄️ Restoring the database from the dummy pack. All your data is being carefully placed!',
					action: 'worrprba_ajax_restore_dummy_pack_database',
					payload: {},
				},
				{
					step: 4,
					name: 'Restore Plugins',
					description: '🔌 Restoring plugins from the dummy pack. Your site\'s functionality is coming back online!',
					action: 'worrprba_ajax_restore_dummy_pack_plugins',
					payload: {},
				},
				{
					step: 5,
					name: 'Restore Uploads',
					description: '📁 Restoring uploads from the dummy pack. All your media and files are being brought back!',
					action: 'worrprba_ajax_restore_dummy_pack_uploads',
					payload: {},
				},
				{
					step: 6,
					name: 'Done',
					description: '🎉 All done! Your dummy pack is fully installed. Everything is set up and ready to go! 🥳',
					action: 'worrprba_ajax_dummy_pack_install_done',
					payload: {},
				}
			];

      set((state) => {
        state.installProcess.process = process;
        // set inProgress to true
        state.installProcess.inProgress = false;
        // set inProgressStep to 1
        state.installProcess.inProgressStep = 1;
        state.installProcess.isModalOpen = true;
        state.installProcess.packData = packData;
      });
    },
    // close install process modal
    setInstallProcessModalOpen: (isOpen) => {
      set((state) => {
        state.installProcess.isModalOpen = isOpen;
      });
    }
	})),
);

export default useDummyPackStore;