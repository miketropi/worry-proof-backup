import React from 'react';
import useBackupStore from '../util/store';
import { getBackups } from '../util/lib';
import BackupTableTools from './BackupTableTools';

const BackupTable = () => {
  const { backups, setBackups } = useBackupStore();
  const [isLoading, setIsLoading] = React.useState(true);
  const [filteredBackups, setFilteredBackups] = React.useState([]);
  
  // selected backups
  const [selectedBackups, setSelectedBackups] = React.useState([]);

  React.useEffect(() => {
    const fetchBackups = async () => {
      try {
        const response = await getBackups();
        setBackups(response.data);
        setFilteredBackups(response.data);
      } catch (error) {
        console.error('Error fetching backups:', error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchBackups();
  }, [setBackups]);

  const handleFilterChange = (date) => {
    if (!date) {
      setFilteredBackups(backups);
      return;
    }

    const filtered = backups.filter(backup => {
      const backupDate = new Date(backup.date);
      const filterDate = new Date(date);
      return backupDate.toDateString() === filterDate.toDateString();
    });

    setFilteredBackups(filtered);
  };

  if (isLoading) {
    return (
      <div className="animate-pulse">
        <div className="h-8 bg-gray-200 rounded mb-4"></div>
        <div className="space-y-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 bg-gray-200 rounded"></div>
          ))}
        </div>
      </div>
    );
  }

  if (!backups || backups.length === 0) {
    return (
      <div className="text-center py-12">
        <div className="bg-gray-50 rounded-lg p-8">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">No backups</h3>
          <p className="mt-1 text-sm text-gray-500">
            Get started by creating a new backup.
          </p>
          <div className="mt-6">
            <button
              type="button"
              className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                className="-ml-1 mr-2 h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
              Create Backup
            </button>
          </div>
        </div>
      </div>
    );
  }

  const handleSelectBackup = (e, backupId) => {
    if (e.target.checked) {
      setSelectedBackups([...selectedBackups, backupId]);
    } else {
      setSelectedBackups(selectedBackups.filter(id => id !== backupId));
    }
  };

  const handleDeleteBackups = (backupsSelected) => {
    let __filteredBackups = [...filteredBackups];
    backupsSelected.forEach(backupId => {
      __filteredBackups = __filteredBackups.filter(backup => backup.id !== backupId);
    });
    setFilteredBackups(__filteredBackups);
    setSelectedBackups([]);
  };

  const handleSelectAllBackups = (e) => {
    if (e.target.checked) {
      setSelectedBackups(filteredBackups.map(backup => backup.id));
    } else {
      setSelectedBackups([]);
    }
  };

  return (
    <div className="bg-white border border-gray-200 rounded-lg">
      <BackupTableTools 
        onFilterChange={handleFilterChange} 
        onDeleteBackups={handleDeleteBackups}
        selectedBackups={selectedBackups} />
      
      {/* Mobile/Tablet View */}
      <div className="block md:hidden">
        {filteredBackups.map((backup) => (
          <div key={backup.id} className="p-4 border-b border-gray-200">
            <div className="flex items-center justify-between mb-2">
              <input 
                type="checkbox" 
                className="form-checkbox h-4 w-4 text-blue-600" 
                checked={selectedBackups.includes(backup.id)}
                onChange={(e) => handleSelectBackup(e, backup.id)}
              />
              <div className="text-sm font-medium text-gray-900">{backup.name}</div>
            </div>
            <div className="space-y-2">
              <div className="flex flex-wrap gap-1">
                {backup.type.map((type, index) => (
                  <span 
                    key={index}
                    className="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 text-gray-800"
                  >
                    {type}
                  </span>
                ))}
              </div>
              <div className="flex justify-between text-sm text-gray-500">
                <span>{backup.date}</span>
                <span>{backup.size}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                  {backup.status}
                </span>
                <div className="space-x-2">
                  <button className="text-blue-600 hover:text-blue-900">Download</button>
                  <button className="text-red-600 hover:text-red-900">Delete</button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Desktop View */}
      <div className="hidden md:block overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500" width="3%">
                <input type="checkbox" className="form-checkbox h-4 w-4 text-blue-600" checked={selectedBackups.length === filteredBackups.length} onChange={handleSelectAllBackups} />
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Name
              </th>
              {/* <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th> */}
              {/* <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th> */}
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Size
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" className="relative px-6 py-3">
                <span className="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {filteredBackups.map((backup) => (
              <tr key={backup.id}>
                <td className="px-6 py-4" width="3%">
                  <input 
                    type="checkbox" 
                    className="form-checkbox h-4 w-4 text-blue-600" 
                    checked={selectedBackups.includes(backup.id)}
                    onChange={(e) => handleSelectBackup(e, backup.id)}
                  />
                </td>
                <td className="px-6 py-4">
                  <div className="text-sm font-medium text-gray-900">
                    {backup.name} 
                    <span className="ml-2 inline-block px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700 align-middle">
                      {backup.date}
                    </span>
                  </div>
                  <div className="flex flex-wrap gap-1 mt-2">
                    {backup.type.map((type, index) => (
                      <span 
                        key={index}
                        className="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors duration-200"
                      >
                        {type}
                      </span>
                    ))}
                  </div>
                  
                </td>
                {/* <td className="px-6 py-4 whitespace-nowrap">
                  
                </td> */}
                {/* <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  
                </td> */}
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {backup.size}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    {backup.status}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button className="text-blue-600 hover:text-blue-900 mr-4" title="Download">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                  </button>
                  <button className="text-red-600 hover:text-red-900" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default BackupTable;
