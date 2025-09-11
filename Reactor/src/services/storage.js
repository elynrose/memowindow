import { ref, uploadBytes, getDownloadURL } from 'firebase/storage';
import { storage } from './firebase';

export class StorageService {
  // Upload waveform files to Firebase Storage
  static async uploadWaveformFiles(waveformBlob, fileName, userId, audioFile = null, playUrl = null) {
    try {
      const results = {};
      
      // Upload waveform image if provided
      if (waveformBlob) {
        const waveformRef = ref(storage, `waveforms/${userId}/${fileName}`);
        const waveformSnapshot = await uploadBytes(waveformRef, waveformBlob);
        results.waveformUrl = await getDownloadURL(waveformSnapshot.ref);
      }
      
      // Upload audio file if provided
      if (audioFile) {
        const audioRef = ref(storage, `audio/${userId}/${audioFile.name}`);
        const audioSnapshot = await uploadBytes(audioRef, audioFile);
        results.audioUrl = await getDownloadURL(audioSnapshot.ref);
      }
      
      return { success: true, ...results };
    } catch (error) {
      console.error('Error uploading files:', error);
      return { success: false, error: error.message };
    }
  }

  // Upload audio file only
  static async uploadAudioFile(audioFile, userId) {
    try {
      const audioRef = ref(storage, `audio/${userId}/${audioFile.name}`);
      const audioSnapshot = await uploadBytes(audioRef, audioFile);
      const audioUrl = await getDownloadURL(audioSnapshot.ref);
      
      return { success: true, audioUrl };
    } catch (error) {
      console.error('Error uploading audio file:', error);
      return { success: false, error: error.message };
    }
  }

  // Upload waveform image only
  static async uploadWaveformImage(waveformBlob, fileName, userId) {
    try {
      const waveformRef = ref(storage, `waveforms/${userId}/${fileName}`);
      const waveformSnapshot = await uploadBytes(waveformRef, waveformBlob);
      const waveformUrl = await getDownloadURL(waveformSnapshot.ref);
      
      return { success: true, waveformUrl };
    } catch (error) {
      console.error('Error uploading waveform image:', error);
      return { success: false, error: error.message };
    }
  }
}