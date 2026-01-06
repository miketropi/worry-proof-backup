import { useState, useEffect } from "react";
import { getDummyPacks, getDummyPacks2 } from "../util/dummyPackLib";

export default function useDummyPack() {
  const [packs, setPacks] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchPacks = async () => {
      setIsLoading(true);
      try {
        const response = await getDummyPacks2();
        
        if(response.success == false) {
          setError(response?.data?.error_message);
          setPacks(null);
          return;
        }

        setPacks(response?.data);
        setError(null);
      } catch (err) {
        setError(err.data);
        setPacks(null);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPacks();
  }, []);

  return { packs, isLoading, error };
}