/*!
 * Piwik - free/libre analytics platform
 *
 * Site selector screenshot tests.
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("SitesManager", function () {
    var selectorToCapture = '#content';

    function assertScreenshotEquals(screenshotName, done, test)
    {
        expect.screenshot(screenshotName).to.be.captureSelector(selectorToCapture, test, done);
    }

    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\SitesManager\\tests\\Fixtures\\ManySites";

    var url = "?module=SitesManager&action=index&idSite=1&period=day&date=yesterday&showaddsite=false";

    it("should load correctly and show page 0", function (done) {
        assertScreenshotEquals("loaded", done, function (page) {
            page.load(url);
        });
    });

    it("should show page 1 when clicking next", function (done) {
        assertScreenshotEquals("page_1", done, function (page) {
            page.click('.SitesManager .paging .next');
        });
    });

    it("should show page 2 when clicking next", function (done) {
        assertScreenshotEquals("page_2", done, function (page) {
            page.click('.SitesManager .paging .next');
        });
    });

    it("should show page 1 when clicking prev", function (done) {
        assertScreenshotEquals("page_1", done, function (page) {
            page.click('.SitesManager .paging .prev');
        });
    });

    it("should search for websites and reset page to 0", function (done) {
        assertScreenshotEquals("search", done, function (page) {
            page.sendKeys(".SitesManager .search input", "Site (1|2|3)");
            page.click('.SitesManager .search .submit');
        });
    });

    it("should page within search result to page 1", function (done) {
        assertScreenshotEquals("search_page_1", done, function (page) {
            page.click('.SitesManager .paging .next');
        });
    });

    it("should search for websites no result", function (done) {
        assertScreenshotEquals("search_no_result", done, function (page) {
            page.sendKeys(".SitesManager .search input", "Ra)nDoMSearChTerm");
            page.click('.SitesManager .search .submit');
        });
    });
});