"use strict";

jQuery(document).ready(function ($) {

    window.redrectionRuleTabDefaultHeader = null;
    let irHeader = document.querySelector('.ir-header');
    if (typeof (irHeader) != 'undefined' && irHeader != null) {
        window.redrectionRuleTabDefaultHeader = irHeader.cloneNode(true).outerHTML;
    }

    let liveSearch = null;

    setupCustomDropdowns();

    /**
     * changing tab and the content
     */
    $(document).on("click", ".tabs .tabs__button:not(.tabs__button--active), .ir-load-tab-action, .ir-load-tab-add-redirect", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const tabs = $(".tabs .tabs__button");
        const el = $(this);
        const tab = $.trim(el.attr("data-tab"));

        const data = new FormData();
        data.append("action", "irLoadTab");
        data.append("tab", tab);

        tabs.addClass("ir-processed");

        const ajax = irGetAjax(data);

        ajax.done(function (r) {
            tabs.removeClass("ir-processed");
            if (r.success) {

                tabs.removeClass("tabs__button--active");
                $("[data-tab=" + tab + "]").addClass("tabs__button--active");
                $(".page__block").html(r.data.content);

                Cookies.set("ir_active_tab", tab, {sameSite: 'Lax'});

                if (tab === "automatic-redirects") {
                    $(".page__block").addClass("automatic-redirects-block");
                } else {
                    $(".page__block").removeClass("automatic-redirects-block");
                }
                //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});

                const irHeader = document.querySelector('.ir-header');
                if (typeof (irHeader) != 'undefined' && irHeader != null) {
                    window.redrectionRuleTabDefaultHeader = irHeader.cloneNode(true).outerHTML;
                }

                //if clicked element is "add redirect" from logs tab - start
                if (el.hasClass("ir-load-tab-add-redirect")) {
                    const requestUrl = el.attr("data-request-url");
                    if (requestUrl) {
                        $(".header .ir-redirect-from").val(requestUrl);
                        $(".header .ir-redirect-to").focus();
                        $("html, body").animate({
                            scrollTop: $(".header.ir-header").offset().top
                        }, 1000);
                    }
                }
                //if clicked element is "add redirect" from logs tab - end


                setupCustomDropdowns();
                irScrollToRight();

            } else {
                // error occured
                // do some specific stuff if needed
                console.error(r.data.status + ": " + r.data.message);
            }
        });
    });

    /**
     * function used to reload the tab content
     * 
     * @returns {void}
     * 
     */
    function reloadCurrentTab() {

        const currentTab = $(".tabs .tabs__button.tabs__button--active").attr("data-tab");
        const tabs = $(".tabs .tabs__button");
        const el = $("[data-tab=" + currentTab + "]");
        const data = new FormData();


        data.append("action", "irLoadTab");
        data.append("tab", currentTab);

        tabs.addClass("ir-processed");

        const ajax = irGetAjax(data);

        ajax.done(function (response) {
            tabs.removeClass("ir-processed");
            if (response.success) {

                tabs.removeClass("tabs__button--active");
                el.addClass("tabs__button--active");
                $(".page__block").html(response.data.content);

                Cookies.set("ir_active_tab", currentTab, {sameSite: 'Lax'});

                if (currentTab === "automatic-redirects") {
                    $(".page__block").addClass("automatic-redirects-block");
                } else {
                    $(".page__block").removeClass("automatic-redirects-block");
                }

                const irHeader = document.querySelector('.ir-header');
                if (typeof (irHeader) != 'undefined' && irHeader != null) {
                    window.redrectionRuleTabDefaultHeader = irHeader.cloneNode(true).outerHTML;
                }

                setupCustomDropdowns();
                irScrollToRight();

            } else {
                // error occured
                // do some specific stuff if needed
                console.error(response.data.status + ": " + response.data.message);
            }
        });
    }
    /**
     * add the redirect
     */
    function refreshAdvancedOptions(form){
        $('#ir-default-settings-form').replaceWith(form)
    }

    function hideForm(){
        if(!$("#ir-default-settings-form").is(':hidden')){
            $('span.ir-default-settings').trigger('click')
        }
    }

    function showForm(){
        if($("#ir-default-settings-form").is(':hidden')){
            $('span.ir-default-settings').trigger('click')
        }
    }

    /**
     * add the redirect
     */
    $(document).on("click", ".header .ir-add-specific-redirect", function () {
        const el = $(this);
        const id = parseInt(el.attr("data-db-id"));
        const from = $(".header .ir-redirect-from");
        const to = $(".header .ir-redirect-to");

        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());

        const fromValue = $.trim(from.val());
        const toValue = $.trim(to.val());

        if (!fromValue.length) {
            notify({
                autoCloseAfter: 3000,
                type: 'error',
                heading: "Error",
                text: irAjaxJS.notify_3000_01
            });
            return;
        }

        const notAllowedSymbols = /[\<\>\"\'\{\}\[\]\|\\,~\^`;@\$\!\*\(\)]+/g;
        if (fromValue.match(notAllowedSymbols)) {
            notify({
                autoCloseAfter: 5000,
                type: 'error',
                heading: "Error",
                text: irAjaxJS.notify_5000
            });
            return;
        }

        if (!toValue.length) {
            notify({
                autoCloseAfter: 3000,
                type: 'error',
                heading: "Error",
                text: irAjaxJS.notify_3000_02
            });
            return;
        }

        if (toValue.match(notAllowedSymbols)) {
            notify({
                autoCloseAfter: 5000,
                type: 'error',
                heading: "Error",
                text: irAjaxJS.notify_5000
            });
            return;
        }

//        const homeUrl = irAjaxJS.home_url;
//        const adminUrl = irAjaxJS.admin_url;
//
//        const homeUrlWoSlash = homeUrl.replace(/\/$/, "");
//        const adminUrlWoSlash = adminUrl.replace(/\/$/, "");
//        const fromValueWoSlash = fromValue.replace(/\/$/, "");
//
//        if ((homeUrlWoSlash.indexOf(fromValueWoSlash) !== -1 || adminUrlWoSlash.indexOf(fromValueWoSlash) !== -1)) {
//            notify({
//                autoCloseAfter: 5000,
//                type: 'error',
//                heading: "Error",
//                text: irAjaxJS.notify_5000
//            });
//            return;
//        }



        // check the inputs for empty values and prevent requests more than 1 time at once

        if (!el.hasClass("ir-processed")) {

            $(".ir-default-settings-form").trigger('submit')

            el.addClass("ir-processed");
            const settingsForm = $(".ir-default-settings-form");
            const settingsJson = JSON.stringify(getSettingsData(settingsForm));

            const data = new FormData();
            data.append("action", "irAddRedirect");
            data.append("id", id);
            data.append("from", fromValue);
            data.append("to", toValue);
            data.append("selected", selectedVal);
            data.append("data", settingsJson);


            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                // console.log(2)
                el.removeClass("ir-processed");
                if (r.success) {
                    from.val("");
                    to.val("");

                    if(r.data.form){
                        const loadNewData = new FormData();
                        loadNewData.append("action", "irLoadSettings");
                        const ajax2 = irGetAjax(loadNewData);
                        ajax2.done(function (res) {
                            // console.log(3)
                            // console.log(res)
                            refreshAdvancedOptions(res.data.content);
                            hideForm();
                        })
                    }

                    if (id) {
                        el.text(irAjaxJS.text_01);
                        $(".ir-header__heading").text(irAjaxJS.text_02);
                    }

                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    } else {
                        $(".table-select-all").removeClass("ir-hidden");
                        $(".custom-pagination").removeClass("ir-hidden");
                    }

                    irSetVisibilities(r);

                    $(".ir-reload-clear").val("");
                    el.attr("data-db-id", 0);

                    $(".ir-instant-edit-from-" + id).val(fromValue);
                    $(".ir-instant-edit-to-" + id).val(toValue);

                    irScrollToRight();
                    irHideCancelBtn();

                    notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});

                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                    if (r.data.status === 'error') {
                        notify({autoCloseAfter: 5000, type: 'error', heading: "Error", text: r.data.message});
                    }
                }
            });
        }
    });

    /**
     * instant edit the redirect
     */
    $(document).on("change", ".flex-table__col .ir-instant-edit-redirect", function () {
        const el = $(this);
        const isCheckbox = el.is(':checkbox');

        let elValue = "";
        if (isCheckbox) {
            elValue = el.is(":checked") ? 1 : 0;
        } else {
            elValue = $.trim(el.val());
        }

        const dbId = parseInt(el.attr("data-db-id"));
        const dbColumn = el.attr("data-db-column");

        const dataArr = [];

        if (!isCheckbox) {
            if (!elValue.length) {
                $(".ir-instant-edit-status-" + dbId).removeAttr("checked");
                dataArr.push({column: 'status', value: 0});
            } else {
                const notAllowedSymbols = /[\<\>\"\'\{\}\[\]\|\\,~\^`;@\$\!\*\(\)]+/g;
        
                if (elValue.match(notAllowedSymbols)) {

                    if (el.hasClass("ir-instant-edit-from-" + dbId)) {
                        notify({
                            autoCloseAfter: 5000,
                            type: 'error',
                            heading: "Error",
                            text: irAjaxJS.notify_5000
                        });
                    } else if (el.hasClass("ir-instant-edit-to-" + dbId)) {
                        notify({
                            autoCloseAfter: 5000,
                            type: 'error',
                            heading: "Error",
                            text: irAjaxJS.notify_5000
                        });
                    }
                    return;
                }
            }
        }

        if ((Number.isInteger(dbId) && dbId > 0) && dbColumn.length && !el.hasClass("ir-processed")) {
            $(".flex-table__col .ir-instant-edit-redirect").addClass("ir-processed");
            dataArr.push({column: dbColumn, value: elValue});
            const data = new FormData();
            data.append("action", "irInstantEditRedirect");
            data.append("id", dbId);
            data.append("data", JSON.stringify(dataArr));

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                $(".flex-table__col .ir-instant-edit-redirect").removeClass("ir-processed");
                if (r.success) {
                    $(".ir-reload-clear").val("");

                    reloadCurrentTab();

                    notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    // TODO
                    if (isCheckbox && (r.data.code == "404_exists" || r.data.code == "all_urls_exists")) {
                        el.prop("checked", Boolean(!elValue));
                        notify({autoCloseAfter: 3000, type: 'error', heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                    } else {
                        console.error(r.data.status + ": " + r.data.message);
                    }
                }
            });
        }
    });

    /**
     * load the data into modal and show it
     */
    $(document).on("click", ".header span.ir-default-settings", function () {
        const el = $(this);

        if (!el.hasClass("ir-processed")) {
            // check existence - toggle show/hide
            const advancedSettingsForm = $("#ir-default-settings-form");
            if(advancedSettingsForm.is(':hidden')){
                // console.log("hidden to show")
                $(advancedSettingsForm).show(300)
                el.html(`Hide advanced options <svg class="ir-header-settings-arrow ir-header-settings-arrow--up" xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="24" height="24"><path d="m12 6.586-8.707 8.707 1.414 1.414L12 9.414l7.293 7.293 1.414-1.414L12 6.586z"/></svg>`)
            }else{
                // console.log("show to hidden")
                $(advancedSettingsForm).hide(300)
                el.html(`Show advanced options <svg class="ir-header-settings-arrow ir-header-settings-arrow--down" xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="24" height="24"><path d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z"/></svg>`)
                return;
            }

            // el.addClass("ir-processed");


            // const data = new FormData();
            // data.append("action", "irLoadSettings");
            // $(".custom-container .page .header.ir-header").append(r.data.content);
            setupCustomDropdowns();
            showCustomDropdown();

            const criteriaDD = $("#ir_hedaer_flex .ir-criterias .custom-dropdown")[0];
            const criteriaDDValue = $(".ir-custom-dropdown-value", criteriaDD);
            if (criteriaDDValue && (criteriaDDValue.val() === "are-404s")) {
                // set inclusion exclusion rules switcher > off
                $(".ir-rules-switcher").prop("checked", false).trigger("change");
                $(".ir-rules-switcher").attr("onclick", "event.preventDefault();event.stopPropagation();");

                // disabling header checkboxes
                $(".settings-box__checkboxes-container .checkboxes-rows__text").attr("onclick", "event.preventDefault();event.stopPropagation();");
                $(".settings-box__checkboxes-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");

                // disabling click on dropdowns, checkboxes
                $(".ir-redirect-settings-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");
                $(".ir-redirect-settings-container .custom-dropdown").attr("onclick", "event.preventDefault();event.stopPropagation();");


                // hiding inclusion / exclusion container
                if (!$(".ir-redirect-settings-container").hasClass("ir-hidden")) {
                    $(".ir-redirect-settings-container").addClass("ir-hidden");
                }

                // disabling inputs
//                        $(".ir-redirect-settings-container input[type=hidden]").attr("disabled", true);
//                        $(".ir-redirect-settings-container input[type=text]").attr("disabled", true);
//                        $(".ir-redirect-settings-container input[type=number]").attr("disabled", true);
//                        $(".ir-redirect-settings-container input[type=checkbox]").attr("disabled", true);
//                        $(".ir-redirect-settings-container .rules-table-input-group__input").attr("disabled", true);
            }

//             const ajax = irGetAjax(data);

//             ajax.done(function (r) {
//                 el.removeClass("ir-processed");
//                 if (r.success) {
//                     // $(".custom-container .page").prepend(r.data.content);
//                     $(".custom-container .page .header.ir-header").append(r.data.content);
//                     setupCustomDropdowns();
//                     showCustomDropdown();

//                     const criteriaDD = $("#ir_hedaer_flex .ir-criterias .custom-dropdown")[0];
//                     const criteriaDDValue = $(".ir-custom-dropdown-value", criteriaDD);
//                     if (criteriaDDValue && (criteriaDDValue.val() === "are-404s" || criteriaDDValue.val() === "all-urls")) {
//                         // set inclusion exclusion rules switcher > off
//                         $(".ir-rules-switcher").prop("checked", false).trigger("change");
//                         $(".ir-rules-switcher").attr("onclick", "event.preventDefault();event.stopPropagation();");

//                         // disabling header checkboxes
//                         $(".settings-box__checkboxes-container .checkboxes-rows__text").attr("onclick", "event.preventDefault();event.stopPropagation();");
//                         $(".settings-box__checkboxes-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");

//                         // disabling click on dropdowns, checkboxes
//                         $(".ir-redirect-settings-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");
//                         $(".ir-redirect-settings-container .custom-dropdown").attr("onclick", "event.preventDefault();event.stopPropagation();");


//                         // hiding inclusion / exclusion container
//                         if (!$(".ir-redirect-settings-container").hasClass("ir-hidden")) {
//                             $(".ir-redirect-settings-container").addClass("ir-hidden");
//                         }

//                         // disabling inputs
// //                        $(".ir-redirect-settings-container input[type=hidden]").attr("disabled", true);
// //                        $(".ir-redirect-settings-container input[type=text]").attr("disabled", true);
// //                        $(".ir-redirect-settings-container input[type=number]").attr("disabled", true);
// //                        $(".ir-redirect-settings-container input[type=checkbox]").attr("disabled", true);
// //                        $(".ir-redirect-settings-container .rules-table-input-group__input").attr("disabled", true);
//                     }

//                     //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
//                 } else {
//                     // error occured
//                     // do some specific stuff if needed
//                     console.log(r.data.status + ": " + r.data.message);
//                 }
//             });
        }
    });

    $(document).on("click", ".flex-table__col .ir-edit-specific-redirect", function () {
        const el = $(this);
        const id = parseInt(el.attr("data-db-id"));

        if (Number.isInteger(id) && id > 0 && !el.hasClass("ir-processed")) {
            el.addClass("ir-processed");
            $(".ir-add-specific-redirect").text(irAjaxJS.text_06);
            $(".ir-header .ir-header__heading").text(irAjaxJS.text_07);

            const data = new FormData();
            data.append("action", "irLoadRedirectSettings");
            data.append("id", id);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    refreshAdvancedOptions(r.data.content)
                    showForm();
                    setupCustomDropdowns(id);
                    showCustomDropdown();
                    if (r.data.from && r.data.to) {
                        $(".ir-redirect-from").val(r.data.from);
                        $(".ir-redirect-to").val(r.data.to);
                        $(".ir-add-specific-redirect").attr("data-db-id", id);
                    }
                    //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});

                    // Show the cancel button
                    // document.querySelector('.cta__cancel-btn').classList.add('cta__cancel-btn--show');
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });

    /**
     * delete a redirect
     */
    $(document).on("click", "#delete-prompt .ir-delete-confirmed", function () {
        const el = $(this);
        const id = parseInt(el.attr("data-db-id"));

        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());

        if (Number.isInteger(id) && id > 0 && !el.hasClass("ir-processed")) {

            el.addClass("ir-processed");

            let currentOffset = 0; // in case of there is no pagination (page count = 1)
            if ($(".custom-pagination .custom-pagination__btn--active").length) {
                currentOffset = parseInt($(".custom-pagination .custom-pagination__btn--active").attr("data-offset"));
            }

            const data = new FormData();
            data.append("action", "irDeleteRedirect");
            data.append("id", id);
            data.append("currentOffset", currentOffset);
            data.append("selected", selectedVal);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    }
                    reloadCurrentTab();
                    irSetVisibilities(r);

                    $(".ir-reload-clear").val("");

                    // remove redirect id from hidden textarea  -- START
                    let parsedVal = selectedVal.length ? JSON.parse(selectedVal) : [];
                    parsedVal = $.grep(parsedVal, function (value) {
                        return value != id;
                    });

                    if (parsedVal.length) {
                        selected.text(JSON.stringify(parsedVal.sort(function (a, b) {
                            return b - a;
                        })));
                    } else {
                        selected.text("");
                    }
                    selected.trigger("change");
                    // remove redirect id from hidden textarea  -- END

                    notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });

    /**
     * bulk edit rediect status
     */
    $(document).on("submit", ".custom-filter .ir-all-selected-form:not(.ir-processed)", function (e) {
        const form = $(this);
        form.addClass("ir-processed");

        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());
        const actionEl = $(".ir-redirect-bulk-edit:checked", form);
        const action = parseInt(actionEl.val());
        const data = new FormData();

        if (action < 0) {
            data.append("action", "irBulkDelete");

            const searchString = $.trim($(".ir-live-search").val());
            if (searchString.length) {
                data.append("search", searchString);
            }
        } else {
            data.append("action", "irStatusBulkEdit");
        }
        data.append("status", action);
        data.append("selected", selectedVal);

        const ajax = irGetAjax(data);

        ajax.done(function (r) {
            form.removeClass("ir-processed");
            if (r.success) {

                reloadCurrentTab();

                if (action >= 0) {
                    const parsedVal = JSON.parse(selectedVal);
                    $.each(parsedVal, function (i, v) {
                        $(".custom-body__data .ir-redirect-chk-" + v).prop("checked", false);
                        const chk = $(".custom-body__data .ir-instant-edit-status-" + v);
                        if (action == 0) {
                            chk.prop("checked", false);
                        } else {
                            chk.prop("checked", true);
                        }
                    });
                } else {
                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);
                    // --IMPORTANT-- changing "selected" textarea and trigger change after replacing the content --START
                    $(".custom-body .ir-selected-redirects").text("").trigger("change");
                    // --IMPORTANT-- changing "selected" textarea and trigger change after replacing the content --END

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    } else {
                        //                    $(".table-select-all").removeClass("ir-hidden");
                        //                    $(".custom-pagination").removeClass("ir-hidden");
                    }

                    irSetVisibilities(r);
                }

                selected.text("").trigger("change");

                notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
            } else {
                // error occured
                // do some specific stuff if needed
                if (r != 0) {
                    notify({autoCloseAfter: 3000, type: 'error',  heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                    console.error(r.data.status + ": " + r.data.message);
                }
                else {
                    notify({autoCloseAfter: 3000, type: 'error', heading: ucFirst("Error"), text: ucFirst("Something went wrong")});
                }
            }
        });
        e.preventDefault();
        return;
    });


    /**
     * redirections pagination
     */
    $(document).on("click", ".custom-pagination .ir-redirection-pagination", function (e) {
        e.preventDefault();
        const el = $(this);

        if (el.hasClass("custom-pagination__btn--active")) {
            return;
        }

        const countPages = parseInt($(".custom-pagination .ir-count-pages").val());
        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());

        const offset = irGetPaginationOffset(el, countPages);
        const page = offset + 1;

        if (Number.isInteger(offset) && !el.hasClass("ir-processed")) {
            $(".custom-pagination .ir-redirection-pagination").addClass("ir-processed");

            const data = new FormData();
            data.append("action", "irRedirectionPageContent");
            data.append("offset", offset);
            data.append("selected", selectedVal);

            const searchString = $.trim($(".ir-live-search").val());
            if (searchString.length) {
                data.append("search", searchString);
            }

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                $(".custom-pagination .ir-redirection-pagination").removeClass("ir-processed");
                if (r.success) {
                    $(".custom-body__data .flex-table__body").html(r.data.content);
                    $(".custom-pagination .ir-redirection-pagination").removeClass("custom-pagination__btn--active");
                    $(".custom-pagination .ir-redirection-pagination.ir-page-" + page).addClass("custom-pagination__btn--active");
                    if (offset > 0) {
                        $(".custom-pagination .ir-prev-page").removeClass("ir-hidden");
                    } else {
                        $(".custom-pagination .ir-prev-page").addClass("ir-hidden");
                    }

                    if (page == countPages) {
                        $(".custom-pagination .ir-next-page").addClass("ir-hidden");
                    } else {
                        $(".custom-pagination .ir-next-page").removeClass("ir-hidden");
                    }

                    // HANDLE SELECT ALL CURRENT PAGE CHECKBOXES STATUS --START
                    const hasUnchecked = $(".flex-table__col .ir-redirect-chk:not(:checked)").length;
                    if (hasUnchecked) {
                        $(".custom-body__data .ir-select-all-specific-redirects-chk").prop("checked", false);
                    } else {
                        $(".custom-body__data .ir-select-all-specific-redirects-chk").prop("checked", true);
                    }
                    // HANDLE SELECT ALL CURRENT PAGE CHECKBOXES STATUS --END

                    //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });


    /**
     * ajax live search
     */
    $(document).on("keyup", ".ir-live-search", function () {
        irSearch($(this));
    });

    $(document).on("search", ".ir-live-search", function () {
        irSearch($(this));
    });

    function irSearch(obj) {
        const searchInput = obj;
        const searchString = $.trim(searchInput.val());

        if (searchString.length >= 1) {
            searchInput.attr("data-char-count", searchString.length);
        }


        if (searchString.length || (searchInput.attr("data-char-count") >= 1)) {
            if (liveSearch !== null) {
                liveSearch.abort();
                liveSearch = null;
            }


            const data = new FormData();
            data.append("action", "irLiveSearch");
            data.append("search", searchString);

            if (!searchString.length && (searchInput.attr("data-char-count") >= 1)) {
                data.append("showAll", 1);
            }

            liveSearch = irGetAjax(data);

            liveSearch.done(function (r) {
                if (r.success) {

                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);
                    // --IMPORTANT-- changing "selected" textarea and trigger change after replacing the content --START
                    $(".custom-body .ir-selected-redirects").text("").trigger("change");
                    // --IMPORTANT-- changing "selected" textarea and trigger change after replacing the content --END

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    } else {
                        // $(".table-select-all").removeClass("ir-hidden");
                        // $(".custom-pagination").removeClass("ir-hidden");
                    }

                    if (r.data.countRedirects > 0) {
                        $(".custom-body").removeClass("ir-hidden");
                    }


                    // notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        } else {
            const data = new FormData();
            data.append("action", "irRedirectionPageContent");
            data.append("offset", 0);
        }
    }

    $(document).on("click", ".ir-select-all-specific-redirects", function () {
        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());

        const data = new FormData();
        data.append("action", "irSelectAll");
        data.append("selected", selectedVal);

        const searchString = $.trim($(".ir-live-search").val());
        if (searchString.length) {
            data.append("search", searchString);
        }

        liveSearch = irGetAjax(data);

        liveSearch.done(function (r) {
            if (r.success) {

                const checkboxIds = JSON.parse(r.data.selected);
                if (checkboxIds.length) {
                    $(".flex-table__col .ir-redirect-chk").each(function (i, v) {
                        const chk = $(this);
                        const chkDbId = chk.attr("data-db-id");
                        if ($.inArray(chkDbId, checkboxIds) !== -1) {
                            chk.prop("checked", true);
                        }
                    });

                    selected.text(r.data.selected);
                } else {
                    $(".flex-table__col .ir-redirect-chk").each(function (i, v) {
                        $(this).prop("checked", false);
                    });
                    selected.text("");
                }
                selected.trigger("change");

                notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
            } else {
                // error occured
                // do some specific stuff if needed
                console.error(r.data.status + ": " + r.data.message);
            }
        });

    });

    $(document).on("submit", ".ir-default-settings-form", function (e) {
        const form = $(this);
        // const settings = $(".ir-default-settings");
        const obj = getSettingsData(form);

        const settingsJson = JSON.stringify(obj);
        const data = new FormData();
        data.append("data", settingsJson);
        let id = 0;

        if ($.trim($(".ir-default-settings-save").attr("data-db-id")).length) {
            id = parseInt($(".ir-default-settings-save").attr("data-db-id"));
            if (Number.isInteger(id) && id > 0) {
                data.append("id", id);
            }
            data.append("action", "irSaveRedirectSettings");
        } else {
            data.append("action", "irSaveSettings");
        }

        const ajax = irGetAjax(data);
        ajax.done(function (r) {
            if (r.success) {
                // console.log(1)
                if (r.data.type && $("#ir-redirect-code-" + id).length) {
                    $("#ir-redirect-code-" + id).text(r.data.type);
                }
                if (r.data.content) {
                    // iC: Not handled this way anymore
                    // $("div.ir-settings-paragraph").replaceWith(r.data.content);
                    // hideForm();
                }
                // $('span.ir-default-settings').trigger('click')

                // notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
            } else {
                // error occured
                // do some specific stuff if needed
                console.error(r.data.status + ": " + r.data.message);
            }
        });

        // settings.text(settingsJson);

        e.preventDefault();
        return;
    });


    /***************************************************************************/
    /************************ REDIRECTION RULES --START ************************/
    /***************************************************************************/


    /**
     * add a redirection rule
     */
    $(document).on("click", ".ir-add-redirect-rule", function () {
        const el = $(this);
        const id = parseInt(el.attr("data-db-id"));
        el.removeClass("ir-hidden");

        const rules = {};
        const criterias = {};
        const action = {};
        const homeUrl = irAjaxJS.home_url;
        const adminUrl = irAjaxJS.admin_url;
        let doAjax = false;
        let index = 0;
        let hasError = false;
        $(".ir-rules-container .ir-header-flex .ir-criterias", document).each(function (contI, cont) {
            let cName = $("input[name=criteria]", cont).length ? $("input[name=criteria]", cont).val() : "";
            let cValue = null;
            if ($("input[name=criteria_value]", cont).is(":disabled")) {
                cValue = $("input[name=criteria_value_dd]", cont).length ? $("input[name=criteria_value_dd]", cont).val() : "";
            } else {
                cValue = $("input[name=criteria_value]", cont).val();

            }

            if (cName !== "undefined" && cValue !== "undefined" && cName.length && cValue.length) {
                const homeUrlWoSlash = homeUrl.replace(/\/$/, "");
                const adminUrlWoSlash = adminUrl.replace(/\/$/, "");
                const cValueWoSlash = cValue.replace(/\/$/, "");
                const toValueWoSlash = $("input[name=action_value]").val().replace(/\/$/, "");

                const redirectLoopCheck = (toValueWoSlash.toLowerCase() == cValueWoSlash.toLowerCase()) ? true : false;

                if ((cName === "contain" || cName === "start-with") && redirectLoopCheck) {
                  hasError = true;
                  // notify({autoCloseAfter: 5000, type: 'error', heading: "Error", text: irAjaxJS.notify_home_url_used});
                  // return;
                }

                if (!hasError) {
                    criterias[index] = {"criteria": cName, "value": cValue};
                    index++;
                }
            } else {
                hasError = true;
            }
        });

        if (hasError) {
            notify({autoCloseAfter: 5000, type: 'error', heading: "Error", text: irAjaxJS.notify_5000});
            return;
        }

        const actionCont = $(".ir-rules-container .ir-header-flex .ir-actions", document)[0];
        let aName = $("input[name=action]", actionCont).length ? $("input[name=action]", actionCont).val() : "";
        let aValue = null;
        if ($("input[name=action_value]", actionCont).is(":disabled")) {
            aValue = $("input[name=action_value_dd]", actionCont).length ? $.trim($("input[name=action_value_dd]", actionCont).val()) : "";
        } else {
            aValue = $.trim($("input[name=action_value]", actionCont).val());
        }

        if (aName !== "undefined" && aValue !== "undefined" && aName.length && aValue.length) {
            action["name"] = aName;

            if (aName === "a-specific-url") {
                const urlPattern = /(^|\s)https?:\/\/[^\'\"\s]+(?:\s|$)/g;
                if (aValue.match(urlPattern)) {
                    action["value"] = aValue;
                } else {
                    hasError = true;
                }
            } else {
                action["value"] = aValue;
            }
        } else {
            hasError = true;
        }

        if (aName === 'urls-with-removed-string') {
            action["name"] = aName;
            action["value"] = aValue;
            hasError = false;
        }

        if (hasError) {
            notify({autoCloseAfter: 5000, type: 'error', heading: "Error", text: irAjaxJS.notify_5000});
            return;
        }

        if (!$.isEmptyObject(criterias) && !$.isEmptyObject(action)) {
            rules["criterias"] = criterias;
            rules["action"] = action;
            doAjax = true;
        }


        if (doAjax && !el.hasClass("ir-processed")) {

            $(".ir-default-settings-form").trigger('submit')

            const settingsForm = $(".ir-default-settings-form");
            const settingsJson = JSON.stringify(getSettingsData(settingsForm));

            const data = new FormData();
            data.append("action", "irAddRedirectRule");
            data.append("id", id);
            data.append("rules", JSON.stringify(rules));
            data.append("data", settingsJson);

            el.addClass("ir-processed");

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {

                    reloadCurrentTab();

                    if(r.data.form){
                        const loadNewData = new FormData();
                        loadNewData.append("action", "irLoadSettings");
                        const ajax2 = irGetAjax(loadNewData);
                        ajax2.done(function (res) {
                            // console.log(3)
                            // console.log(res)
                            refreshAdvancedOptions(res.data.content);
                            hideForm();
                        })
                    }

                    if (id) {
                        el.text(irAjaxJS.text_08);
                        $(".ir-header .ir-header__heading").text(irAjaxJS.text_03);
                    }

                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    } else {
                        $(".table-select-all").removeClass("ir-hidden");
                        $(".custom-pagination").removeClass("ir-hidden");
                    }

                    irSetVisibilities(r);

                    $(".ir-reload-clear").val("");
                    el.attr("data-db-id", 0);

                    $(".ir-rules-container .ir-header-flex", document).each(function (contI, cont) {
                        const rowContainer = $(cont);
                        if (rowContainer.hasClass("mt-50")) {
                            rowContainer.remove();
                        } else {
                            $(".header__2nd-dropdown-container.header__2nd-dropdown-container--active").removeClass("header__2nd-dropdown-container--active");
                            $(".custom-dropdown", rowContainer).each(function (i, customDD) {
                                const ddSelectedContainer = $("[data-selected-dropdown-item-id]", customDD);
                                ddSelectedContainer.attr("data-selected-dropdown-item-id", "false");
                                setupCustomDropdowns();
                            });
                        }
                    });

                    if (r.data.html) {
                        $(".ir-redirect-" + id).replaceWith(r.data.html);
                    }

                    irScrollToRight();
                    irHideCancelBtn();

                    notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    if (r.data.code || r.data.status === 'error') {
                        notify({autoCloseAfter: 5000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                    } else {
                        console.error(r.data.status + ": " + r.data.message);
                    }
                }
            });
        }
    });

    function decodeHTMLEntities(encodedString) {
        var doc = new DOMParser().parseFromString(encodedString, 'text/html');
        return doc.documentElement.textContent;
      }

    /**
     * editing a redirection rule
     */
    $(document).on("click", ".flex-table__col .ir-edit-redirect-rule", function () {
        const el = $(this);
        const id = parseInt(el.attr("data-db-id"));
        const isIncExcEnabled = el.attr("data-incexc");

        if (Number.isInteger(id) && id > 0 && !el.hasClass("ir-processed")) {
            el.addClass("ir-processed");
            $(".ir-add-redirect-rule").text(irAjaxJS.text_04);
            $(".ir-header .ir-header__heading").text(irAjaxJS.text_05);

            const data = new FormData();
            data.append("action", "irLoadRedirectSettings");
            data.append("id", id);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    refreshAdvancedOptions(r.data.content)
                    showForm();
                    setupCustomDropdowns(id);
                    showCustomDropdown();
                    if (r.data.criterias && r.data.action) {
                        const criterias = r.data.criterias;
                        const action = r.data.action;

                        //removing all other criteria rows except the default one
                        $(".ir-rules-container .ir-header-flex:not(#ir_hedaer_flex)").remove();

                        // adding single criteria html
                        // starting from 1, by default one row exists
                        for (let i = 1; i < criterias.length; i++) {
                            $(".ir-add-another-criteria", document).trigger("click");
                        }

                        $.each(criterias, function (index, obj) {
                            setTimeout(() => {

                                const ddContainer = $(".ir-header-flex:not(.header__flex--hidden-placeholder)")[index];

                                $(".header__flex-inputs", ddContainer).removeClass("header__flex-inputs--disabled");
                                // TODO ???
                                // $(".header__flex-inputs", ddContainer).find( "input" ).prop( "disabled", false );
                                const criteriaDD = $("[data-name=criteria]", ddContainer);
                                const criteriaIndex = $("[data-value=" + obj.criteria + "]", criteriaDD).attr("data-dropdown-item-id");

                                if (obj.criteria === "have-permalink-structure") {
                                    const criteriaValueDD = $("[data-name=criteria_value_dd]", ddContainer);
                                    const criteriaValueIndex = $("[data-value=" + obj.value + "]", criteriaValueDD).attr("data-dropdown-item-id");
                                    $("[data-selected-dropdown-item-id]", criteriaValueDD).attr("data-selected-dropdown-item-id", criteriaValueIndex);
                                    $("[data-dropdown-item-id=" + criteriaIndex + "]", criteriaDD).trigger("click");
                                } else {
                                    $(".ir-redirect-from", ddContainer).val(decodeHTMLEntities(obj.value));
                                }

                                if (index == 0) {
                                    const actionDD = $("[data-name=action]", ddContainer);
                                    const actionIndex = $("[data-value=" + action.name + "]", actionDD).attr("data-dropdown-item-id");

                                    if (action.name === "new-permalink-structure") {
                                        const actionValueDD = $("[data-name=action_value_dd]", ddContainer);
                                        const actionValueIndex = $("[data-value=" + action.value + "]", actionValueDD).attr("data-dropdown-item-id");
                                        $("[data-selected-dropdown-item-id]", actionValueDD).attr("data-selected-dropdown-item-id", actionValueIndex);
                                        $("[data-dropdown-item-id=" + actionIndex + "]", actionDD).trigger("click");
                                    } else {
                                        $(".ir-redirect-to", ddContainer).val(action.value);
                                    }
                                    $("[data-selected-dropdown-item-id]", actionDD).attr("data-selected-dropdown-item-id", actionIndex);

                                    /* Remove input for urls-with-removed-string */
                                    if (action.name === 'urls-with-removed-string') {
                                        document.querySelector('input[name="action_value"]').style.display = 'none';
                                        $("input[name=action_value]", ddContainer).attr("disabled", true);
                                    } else {
                                        document.querySelector('input[name="action_value"]').style.display = '';
                                        // TODO ???
                                        // $("input[name=action_value]", ddContainer).removeAttr("disabled");

                                    }
                                }

                                $("[data-selected-dropdown-item-id]", criteriaDD).attr("data-selected-dropdown-item-id", criteriaIndex);

                                setupCustomDropdowns();

                            }, 25);

                        });

                        $(".ir-add-redirect-rule").attr("data-db-id", id);

                    }
                    //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});

                    // Show the cancel button
                    document.querySelector('.cta__cancel-btn').classList.add('cta__cancel-btn--show');


                    // disbaling inclusion exclusion rules and options
                    if ((typeof isIncExcEnabled === "undefined" || isIncExcEnabled === false)) {
                        $(".ir-rules-switcher").attr("onclick", "event.preventDefault();event.stopPropagation();");
                        $(".ir-default-settings-form .settings-box__checkboxes-container .checkboxes-rows__text").attr("onclick", "event.preventDefault();event.stopPropagation();");
                        $(".ir-default-settings-form .settings-box__checkboxes-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");
                    }
                } else {
                    // error occured
                    // do some specific stuff if needed
                    if (r.data.code) {
                        notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                    } else {
                        console.error(r.data.status + ": " + r.data.message);
                    }
                }
            });
        }
    });

    /***************************************************************************/
    /************************ REDIRECTION RULES --END **************************/
    /***************************************************************************/

    /***************************************************************************/
    /*********************** LIVE CHAT SUPPORT --START *************************/
    /***************************************************************************/

    $('#irrp_support_chat').on('click', function () {

        if ($('#support-irrp').length === 0) {
            $('#irrp_container_main').append('<script id="support-irrp" src="//code-eu1.jivosite.com/widget/sQaTKn5ugM" async></script>');
            setTimeout(function () {
                $('#irrp_support_chat').hide();
            }, 100);
            var loaded = false;
            let loadinter = setInterval(function () {
                if (loaded == true)
                    clearInterval(loadinter);
                if (typeof window.jivo_api !== 'undefined') {
                    window.jivo_api.open()
                    loaded = true;
                }
            }, 30);
        }

    });

    /***************************************************************************/
    /************************ LIVE CHAT SUPPORT --END **************************/
    /***************************************************************************/



    /***************************************************************************/
    /************************ REDIRECTS IMPORT --START *************************/
    /***************************************************************************/

    $(document).on("change", "#irrp_import_redirects", function (e) {
        const inp = $(this);
        const files = inp[0].files;
        const nonce = inp.attr("data-nonce");

        if (!files.length || !nonce.length) {
            return;
        }

        const data = new FormData();
        data.append("action", "irrp_import");
        $.each(files, function (i, file) {
            data.append("_file", file);
        });
        data.append("_irrp_nonce", nonce);

        const ajax = irGetAjax(data);
        ajax.done(function (r) {
            if (typeof r === "object") {
                let status = "error";
                let statusMessage = "Error";

                if (r.success) {
                    status = "success";
                    statusMessage = "Success";

//                    if (r.data.type === irAjaxJS.type_redirection) {
                    $(".custom-pagination").html(r.data.pagination);
                    $(".custom-body__data .flex-table__body").html(r.data.content);

                    if (r.data.countPages < 2) {
                        $(".table-select-all").addClass("ir-hidden");
                    } else {
                        $(".table-select-all").removeClass("ir-hidden");
                        $(".custom-pagination").removeClass("ir-hidden");
                    }

                    irSetVisibilities(r);
//                    }
                }
                notify({
                    autoCloseAfter: 3000,
                    type: status,
                    heading: statusMessage,
                    text: r.data.message
                });
            }
        });
    });

    /***************************************************************************/
    /************************ REDIRECTS IMPORT --END ***************************/
    /***************************************************************************/

    /***************************************************************************/
    /****************************** LOGS --START *******************************/
    /***************************************************************************/

    /**
     * logs status change
     */
    $(document).on("change", ".ir-redirection_logs_status", function (e){
        e.preventDefault();
        const el = $(this);
        

        const data = new FormData();

        const enabled = el.is(":checked") == true ? 1 : 0;
        data.append("action", "irLogStatusChange");
        data.append("log_status", enabled);

        if (!el.hasClass("ir-processed")) {

            el.addClass("ir-processed");
            const ajax = irGetAjax(data);
            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    notify({ autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message) });
                } else {
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }

    })
    /**
     * logs pagination
     */
    $(document).on("click", ".custom-pagination .ir-log-pagination", function (e) {
        e.preventDefault();
        const el = $(this);

        if (el.hasClass("custom-pagination__btn--active")) {
            return;
        }

        const countPages = parseInt($(".custom-pagination .ir-count-pages").val());

        const offset = irGetPaginationOffset(el, countPages);
        const page = offset + 1;

        const filter = $(".redirect-table .ir-redirection_logs_filter");
        const logType = $.trim(filter.val());

        if (Number.isInteger(offset) && !el.hasClass("ir-processed")) {
            $(".custom-pagination .ir-log-pagination").addClass("ir-processed");

            const data = new FormData();
            data.append("action", "irLogPageContent");
            data.append("offset", offset);
            data.append("log_type", logType);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                $(".custom-pagination .ir-log-pagination").removeClass("ir-processed");
                if (r.success) {
                    $(".redirect-table .ir-redirect-table-tbody").html(r.data.content);
                    $(".custom-pagination .ir-log-pagination").removeClass("custom-pagination__btn--active");
                    $(".custom-pagination .ir-log-pagination.ir-page-" + page).addClass("custom-pagination__btn--active");
                    if (offset > 0) {
                        $(".custom-pagination .ir-prev-page").removeClass("ir-hidden");
                    } else {
                        $(".custom-pagination .ir-prev-page").addClass("ir-hidden");
                    }

                    if (page === countPages) {
                        $(".custom-pagination .ir-next-page").addClass("ir-hidden");
                    } else {
                        $(".custom-pagination .ir-next-page").removeClass("ir-hidden");
                    }

                    //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });

    /**
     * New logs pagination
     */
    $(document).on("click", ".ir-show-more", function (e) {
        e.preventDefault();
        const el = $(this);
        const offset = parseInt(el.attr("data-offset"));
        const logType = $.trim($(".ir-redirection_logs_filter").val());

        if (Number.isInteger(offset) && !el.hasClass("ir-processed")) {
            el.addClass("ir-processed");

            const data = new FormData();
            data.append("action", "irLogPageContent");
            data.append("offset", offset);
            data.append("log_type", logType);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    $(".redirect-table .ir-redirect-table-tbody").append(r.data.content);

                    el.attr("data-offset", offset + 1);
                    if (el.attr("data-offset") >= el.attr("data-max-offset")) {
                        el.addClass("ir-hidden");
                    }
                } else {
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });



    $(document).on("change", ".redirect-table .ir-redirection_logs_filter:not(.ir-processed)", function (e) {
        e.preventDefault();
        const el = $(this);
        const logType = $.trim(el.val());
        const page = 1;

        if (logType.length) {
            el.addClass("ir-processed");

            if (logType === "all" || logType === "404s") {
                const downloadBtn = $(".ir-download-logs");
                const downloadUrl = new URL(downloadBtn.attr("href"));
                const downloadNewUrl = irNewUrlWithFilter(downloadUrl, logType);
                downloadBtn.attr("href", downloadNewUrl);

                const deleteBtn = $(".ir-delete-logs");
                const deleteUrl = new URL(deleteBtn.attr("href"));
                const deleteNewUrl = irNewUrlWithFilter(deleteUrl, logType);
                deleteBtn.attr("href", deleteNewUrl);
            }

            const data = new FormData();
            data.append("action", "irLogFilter");
            data.append("log_type", logType);

            const ajax = irGetAjax(data);
            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    $(".custom-pagination").html(r.data.pagination);
                    $(".redirect-table .ir-redirect-table-tbody").html(r.data.content);
//                    $(".custom-pagination .ir-log-pagination").removeClass("custom-pagination__btn--active");
//                    $(".custom-pagination .ir-log-pagination.ir-page-" + page).addClass("custom-pagination__btn--active");
//                    if (offset > 0)
//                        $(".custom-pagination .ir-prev-page").removeClass("ir-hidden");
//                    } else {
//                        $(".custom-pagination .ir-prev-page").addClass("ir-hidden");
//                    }
//
//                    if (page === countPages) {
//                        $(".custom-pagination .ir-next-page").addClass("ir-hidden");
//                    } else {
//                        $(".custom-pagination .ir-next-page").removeClass("ir-hidden");
//                    }

                    //notify({autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message)});
                } else {
                    // error occured
                    // do some specific stuff if needed
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });

    // saving dropdown selected values in cookie for initialization after page reload and cron job
    $(document).on("change", ".redirect-content .ir-redirection_logs_delete", function (e) {
        e.preventDefault();
        const el = $(this);
        const logDelete = $.trim(el.val());

        if (el.hasClass("ir-processed")) {
            return;
        }

        if (logDelete.length) {
            el.addClass("ir-processed");
            const cronLogDeleteOption = logDelete.toLowerCase();

            // set selected id for initialization after page reload
            //Cookies.set("log_auto_delete", logAutoDelete, {expires: 36500});
            //Cookies.set("log_auto_delete_selected_id", logAutoDeleteSelectedId, {expires: 36500});
            const data = new FormData();
            data.append("action", "irCronLogDeleteOption");
            data.append("cron_log_delete_option", cronLogDeleteOption);

            const ajax = irGetAjax(data);

            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    // console.log(r.data.status + ": " + r.data.message);
                } else {
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }

    });

    $(document).on("click", ".ir-delete-logs", function (e) {
        if (!confirm(irAjaxJS.confirm_logs_delete)) {
            e.preventDefault();
            return;
        }
    });


    function irNewUrlWithFilter(url, logType) {
        const urlParams = new URLSearchParams(url.search);

        if (logType === "404s") {
            notify({autoCloseAfter: 3000, heading: "", text: irAjaxJS.notify_3000_04});
        } else {
            notify({autoCloseAfter: 3000, heading: "", text: irAjaxJS.notify_3000_03});
        }

        let newUrl = [url.origin, url.pathname, "?"].join("");
        if (urlParams.has("log_type")) {
            urlParams.set("log_type", logType);
        } else {
            urlParams.append("log_type", logType);
        }

        urlParams.forEach(function (value, key) {
            newUrl += `${key}=${value}&`;
        });

        newUrl = newUrl.substring(0, newUrl.length - 1);
        return newUrl;
    }

    /***************************************************************************/
    /******************************* LOGS --END ********************************/
    /***************************************************************************/

    /***************************************************************************/
    /*********************** AUTO-Redirects ---Start ***************************/
    /***************************************************************************/
    
    // ir-instant-edit-redirect ir-instant-edit-status ir-instant-edit-status-30
    $(document).on("change", ".log-me-where-i-finished", function (e) {
        e.preventDefault();
        const el = $(this);
        

        const data = new FormData();

        const enabled = el.is(":checked") == true ? 1 : 0;
        const settings = JSON.stringify({ "log_me_where_i_finished": enabled });
        data.append("action", "irLogMeWhereIFinished");
        data.append("data", settings);

        if (!el.hasClass("ir-processed")) {

            el.addClass("ir-processed");
            const ajax = irGetAjax(data);
            ajax.done(function (r) {
                el.removeClass("ir-processed");
                if (r.success) {
                    // console.log(r.data.status + ": " + r.data.message);
                    notify({ autoCloseAfter: 3000, heading: ucFirst(r.data.status), text: ucFirst(r.data.message) });
                } else {
                    console.error(r.data.status + ": " + r.data.message);
                }
            });
        }
    });


    /***************************************************************************/
    /*********************** AUTO-Redirects   ---END ***************************/
    /***************************************************************************/
    

    /**
     * @param {type} el the clicked pagination button
     * @param {type} countPages the count of pages
     * @returns Number
     */
    function irGetPaginationOffset(el, countPages) {
        let offset = 0;
        if (el.hasClass("ir-prev-page")) {
            const prevOffset = parseInt($(".custom-pagination .custom-pagination__btn--active").attr("data-offset"));
            offset = prevOffset > 0 ? prevOffset - 1 : 0;
        } else if (el.hasClass("ir-next-page")) {
            const nextOffset = parseInt($(".custom-pagination .custom-pagination__btn--active").attr("data-offset"));
            offset = (nextOffset + 1) > countPages ? countPages : nextOffset + 1;
        } else {
            offset = parseInt(el.attr("data-offset"));
        }

        if (offset < 0) {
            offset = 0;
        } else if (offset > countPages) {
            offset = countPages;
        }
        return offset;
    }


    function irSetVisibilities(r) {
        if (typeof r === "object") {
            if (r.data.countRedirects > 0) {
                $(".custom-body").removeClass("ir-hidden");
                $(".ir-import-redirects").addClass("ir-hidden");
            } else {
                $(".custom-body").addClass("ir-hidden");
                $(".ir-import-redirects").removeClass("ir-hidden");
            }
        }
    }


    function irGetAjax(data) {

        if ((data.get("action") !== "irLoadTab") && $(".ir-redirection-type").length) {
            const redirectionType = $.trim($(".ir-redirection-type").val());
            data.append("redirectionType", redirectionType);
        }

        return $.ajax({
            type: "POST",
            url: ajaxurl + "?nonce=" + irAjaxJS.nonce,
            data: data,
            contentType: false,
            processData: false,
        });
    }

    $(document).on("click", ".ir-header-cancel", function () {
        const currentHeader = document.querySelector('.ir-header');
        const headerNextElementSibling = currentHeader.nextElementSibling;

        const newDiv = document.createElement('div')
        newDiv.innerHTML = window.redrectionRuleTabDefaultHeader
        // console.log(newDiv.firstElementChild)
        currentHeader.parentNode.insertBefore(newDiv.firstElementChild, headerNextElementSibling);
        currentHeader.remove();
    });
});
