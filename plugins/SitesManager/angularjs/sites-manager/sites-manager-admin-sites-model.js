/**
 * Model for Sites Manager. Fetches only sites one has at least Admin permission.
 */
(function () {
    angular.module('piwikApp').factory('sitesManagerAdminSitesModel', sitesManagerAdminSitesModel);

    sitesManagerAdminSitesModel.$inject = ['piwikApi'];

    function sitesManagerAdminSitesModel(piwikApi)
    {
        var model = {
            sites        : [],
            isLoading    : false,
            pageSize     : 25,
            currentPage  : 0,
            offsetStart  : 0,
            offsetEnd    : 25,
            hasPrev      : false,
            hasNext      : false,
            previousPage: previousPage,
            nextPage: nextPage,
            searchSite: searchSite,
            fetchLimitedSitesWithAdminAccess: fetchLimitedSitesWithAdminAccess
        };

        return model;

        function onError ()
        {
            setSites([]);
        }

        function setSites(sites)
        {
            model.sites = sites;

            var numSites    = sites.length;
            model.offsetEnd = model.offsetStart + numSites;
            model.hasNext   = numSites === model.pageSize;
        }

        function setCurrentPage(page)
        {
            if (page < 0) {
                page = 0;
            }

            model.currentPage = page;
            model.offsetStart = model.currentPage * model.pageSize;
            model.offsetEnd   = model.offsetStart + model.pageSize;
            model.hasPrev     = page >= 1;
        }

        function previousPage()
        {
            setCurrentPage(model.currentPage - 1);
            fetchLimitedSitesWithAdminAccess();
        }

        function nextPage()
        {
            setCurrentPage(model.currentPage + 1);
            fetchLimitedSitesWithAdminAccess();
        }

        function searchSite (term)
        {
            setCurrentPage(0);
            fetchLimitedSitesWithAdminAccess(term);
        }

        function fetchLimitedSitesWithAdminAccess(searchTerm)
        {
            if (model.isLoading) {
                piwikApi.abort();
            }

            model.isLoading = true;

            var params = {
                method: 'SitesManager.getSitesWithAdminAccess',
                fetchAliasUrls: true,
                filter_offset: model.offsetStart,
                filter_limit: model.pageSize,
            };

            if (searchTerm) {
                params['filter_column[]'] = ['name', 'main_url', 'idsite'];
                params.filter_pattern = searchTerm;
            }

            return piwikApi.fetch(params).then(function (sites) {

                if (!sites) {
                    onError();
                    return;
                }

                if (0 === sites.length && !searchTerm) {
                    model.previousPage(); // we must have reached the end
                } else {
                    setSites(sites);
                }

            }, onError)['finally'](function () {
                model.isLoading = false;
            });
        }
    }
})();
