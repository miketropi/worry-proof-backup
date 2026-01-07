import { useState, useEffect } from "react";
import { doPreinstall } from "../util/dummyPackLib";

export default function useDummyPackPreinstall(packID) {
  const [resultPreinstall, setResultPreinstall] = useState(null);
  const [errorPreinstall, setErrorPreinstall] = useState(null);
  const [loadingPreinstall, setLoadingPreinstall] = useState(false);

  useEffect(() => {
    if(!packID) return;

    const fetchPreinstall = async () => {
      setLoadingPreinstall(true);
      const response = await doPreinstall(packID);

      if(response?.error) {
        setLoadingPreinstall(false);
        setErrorPreinstall(response.error);
        setResultPreinstall(false);
        return;
      }

      setErrorPreinstall(null);
      setResultPreinstall(true);
      setLoadingPreinstall(false);
    }

    fetchPreinstall();
  }, [packID]);

  return { resultPreinstall, errorPreinstall, loadingPreinstall };
}