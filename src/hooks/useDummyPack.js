import { useState, useEffect } from "react";
import { getDummyPacks } from "../util/dummyPackLib";

export default function useDummyPack() {
  const [packs, setPacks] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchPacks = async () => {
      setIsLoading(true);
      try {
        const response = await getDummyPacks();
        
        if(response?.success?.valueOf() == false) {
          // console.log('getDummyPacks', response?.success?.valueOf() == false);
          setError(response.data);
          setPacks(null);
          return;
        }

        setPacks(response);
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