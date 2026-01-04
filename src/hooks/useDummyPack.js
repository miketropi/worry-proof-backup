import { useState, useEffect } from "react";
import { getDummyPacks } from "../util/dummyPackLib";

export default function useDummyPack() {
  const [packs, setPacks] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchPacks = async () => {
      setIsLoading(true);
      try {
        const response = await getDummyPacks();
        setPacks(response);
        setError(null);
      } catch (err) {
        setError(err.message);
        setPacks([]);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPacks();
  }, []);

  return { packs, isLoading, error };
}