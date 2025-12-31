import numeral from 'numeral';
import { DateTime } from 'luxon';

export function formatRupiah(value) {
  return numeral(value).format('0,0[.]00');
}

export function parseRupiah(value) {
  return numeral(value).value();
}

export function formatPercentage(value) {
  return numeral(value).format('0,0[.]00%');
}

export function formatDate(date) {
  const options = { day: 'numeric', month: 'long', year: 'numeric' };

  return new Date(date).toLocaleDateString('id-ID', options);
}

export function formatDateTime(date) {
  const options = {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: 'numeric',
    minute: 'numeric'
  };

  return new Date(date).toLocaleDateString('id-ID', options);
}

export function formatToClientTimezone(date, format = 'dd MMM yyyy HH:mm') {
  const originalDate = DateTime.fromISO(date, { zone: 'utc' });

  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  return originalDate.setZone(timezone).setLocale('id').toFormat(format);
}

export function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
