// Единственная копия запросов страниц-отчётов (закупка, доноры): JSON для показа
// и xlsx того же отчёта. Ошибка уходит в снекбар, промис отклоняется дальше.
import _ from 'lodash'
import FileSaver from 'file-saver'

/** JSON отчёта для показа на странице. */
export function getReport(url, params, commit) {
    return axios.get(url, {params})
        .then(response => response.data)
        .catch(error => {
            commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
            throw error;
        });
}

/** Скачивание xlsx с заданным именем файла (как SAVE в model.js, но с указанием url). */
export function exportXlsx(url, payload, commit) {
    const query = _.cloneDeep(payload);
    const filename = query.filename;
    delete query.filename;

    return axios.get(url, {params: query, responseType: 'blob'})
        .then(response => {
            FileSaver.saveAs(response.data, filename || 'report.xlsx');
            return response;
        })
        .catch(error => {
            commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
            throw error;
        });
}
