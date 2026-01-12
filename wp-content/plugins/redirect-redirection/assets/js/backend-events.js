"use strict";

function organizeRedirectionRules(media) {
    if (media.matches) { // If media query matches
        // put the rules outside
        var rulesContainer = document.querySelector('.ir-rules-container.ir-rules-form')
        document.querySelectorAll('.header__flex.ir-header-flex.mt-50.mt-1140-10').forEach(function (headerFlex) {
            rulesContainer.appendChild(headerFlex);
        });
    } else {
        // put the rules inside
        var arrowSvg = document.querySelector('#ir_hedaer_flex .header__arrow-svg')
        document.querySelectorAll('.header__flex.ir-header-flex.mt-50.mt-1140-10').forEach(function (headerFlex) {
            arrowSvg.parentNode.insertBefore(headerFlex, arrowSvg);
        });
    }
}

var media = window.matchMedia("(min-width: 1140px)");
organizeRedirectionRules(media);
media.addListener(organizeRedirectionRules);

function removeRule(e) {
    e.target.closest(".header__flex").classList.add("header__flex--hidden-placeholder");
    setTimeout(() => {
        e.target.closest(".header__flex").remove();
    }, 550);
}

const ucFirst = (s) => {
    if (typeof s !== "string") {
        return "";
    }
    return s.charAt(0).toUpperCase() + s.slice(1);
};

jQuery(document).ready(function ($) {

    /* CURRENT PAGE CHECKBOXES CHECK/UNCHECK --START */
    $(document).on("change", ".custom-body__data .ir-select-all-specific-redirects-chk", function () {
        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());
        let parsedVal = selectedVal.length ? JSON.parse(selectedVal) : [];

        const unchecked = $(".flex-table__col .ir-redirect-chk:not(:checked)");

        if (unchecked.length) { // there are unchecked checkboxes, check them and add their ids in "selected" textarea
            unchecked.each(function (i, v) {
                const chk = $(this);
                const chkDbId = chk.attr("data-db-id");
                $(".custom-body__data .ir-redirect-chk-" + chkDbId).prop("checked", true);
                if ($.inArray(chkDbId, parsedVal) === -1) {
                    parsedVal.push(chkDbId);
                }
            });
        } else { // all checkboxes on the current page are checked, uncheck them and remove from "selected" textarea
            $(".flex-table__col .ir-redirect-chk").each(function (i, v) {
                const chk = $(this);
                const chkDbId = chk.attr("data-db-id");
                $(".custom-body__data .ir-redirect-chk-" + chkDbId).prop("checked", false);
                parsedVal = $.grep(parsedVal, function (value) {
                    return value != chkDbId;
                });
            });
        }

        if (parsedVal.length) {
            selected.text(JSON.stringify(parsedVal.sort(function (a, b) {
                return b - a;
            })));
        } else {
            selected.text("");
        }
        selected.trigger("change");
    });
    /* CURRENT PAGE CHECKBOXES SELECT/DESELECT --END */


    /* SINGLE CHECKBOX CHECK/UNCHECK --START */
    $(document).on("change", ".custom-body__data .ir-redirect-chk", function () {
        const chk = $(this);
        const chkDbId = chk.attr("data-db-id");
        const selected = $(".custom-body .ir-selected-redirects");
        const selectedVal = $.trim(selected.val());
        let parsedVal = selectedVal.length ? JSON.parse(selectedVal) : [];

        if (chk.is(":checked")) {
            $(".custom-body__data .ir-redirect-chk-" + chkDbId).prop("checked", true);
            if ($.inArray(chkDbId, parsedVal) === -1) {
                parsedVal.push(chkDbId);
            }
        } else {
            $(".custom-body__data .ir-redirect-chk-" + chkDbId).prop("checked", false);
            parsedVal = $.grep(parsedVal, function (value) {
                return value != chkDbId;
            });
        }

        if (parsedVal.length) {
            selected.text(JSON.stringify(parsedVal.sort(function (a, b) {
                return b - a;
            })));
        } else {
            selected.text("");
        }
        selected.trigger("change");
    });
    /* SINGLE CHECKBOX CHECK/UNCHECK --END */

    /* "SELECTED" TEXTAREA CHANGE EVENT --START */
    $(document).on("change", ".ir-selected-redirects", function () {
        const selected = $(this);
        const selectedVal = $.trim(selected.val());
        if (selectedVal.length) {
            $(".custom-filter .ir-all-selected-form").removeClass("ir-hidden");
        } else {
            $(".custom-filter .ir-all-selected-form").addClass("ir-hidden");
        }

        // get all unchecked visible checkboxes count
        const hasUnchecked = $(".flex-table__col .ir-redirect-chk:not(:checked)").length;
        if (hasUnchecked > 0) {
            $(".ir-select-all-specific-redirects-chk").prop("checked", false);
        } else {
            const hasCheckboxes = $(".flex-table__col .ir-redirect-chk").length;
            if (hasCheckboxes) {
                $(".ir-select-all-specific-redirects-chk").prop("checked", true);
            } else {
                $(".ir-select-all-specific-redirects-chk").prop("checked", false);
            }
        }
    });
    /* "SELECTED" TEXTAREA CHANGE EVENT --END */

    /* DELETE CONFIRMATION SHOW/CLOSE --START */
    $(document).on("animationend webkitAnimationEnd oAnimationEnd", "#delete-prompt", function () {
        if (!$("#delete-prompt").hasClass("custom-prompt--show") && $("#delete-prompt").hasClass("custom-prompt--close")) {
            $("#delete-prompt").removeClass("custom-prompt--close");
        }
    });

    $(document).on("click", ".flex-table__col .ir-delete-confirmation-show", function () {
        $("#delete-prompt").addClass("custom-prompt--show");
        $("#delete-prompt .ir-delete-confirmed").attr("data-db-id", $(this).attr("data-db-id"));
    });

    $(document).on("click", "#delete-prompt .ir-delete-confirmation-close", function () {
        $("#delete-prompt").removeClass("custom-prompt--show").addClass("custom-prompt--close");
    });
    /* DELETE CONFIRMATION SHOW/CLOSE --END */

    /* DEFAULT SETTINGS SHOW/CLOSE --START */

    $(document).on("animationend webkitAnimationEnd oAnimationEnd", ".custom-modal", function (event) {
        var customModal = event.target.closest(".custom-modal")
        if (!customModal.classList.contains("custom-modal--show") && customModal.classList.contains("custom-modal--close")) {
            customModal.classList.remove("custom-modal--close");
            if ($(customModal).attr("id") === "custom-modal") {
                $("#ir-custom-modal").remove(); // removing custom modal
            }

            const from = $(".ir-redirect-from");
            const to = $(".ir-redirect-from");
            const criteria = $(".ir-criteria_value_dd");
            const action = $(".ir-action_value_dd");

            if (($.trim(from.val()).length && $.trim(to.val()).length) ||
                    ($.trim(criteria.val()).length && $.trim(action.val()).length)
                    ) {
                $("html, body").animate({
                    scrollTop: $(".header.ir-header").offset().top
                }, 1000);
            }
        }
    });

    $(document).on("click", "#custom-modal .ir-default-settings-close", function () {
        $("#custom-modal").removeClass("custom-modal--show").addClass("custom-modal--close");
        $("body").css("overflow", "");
    });

    $(document).on("click", "#custom-modal .ir-http-codes-show", function () {
        $("#http-codes-custom-modal").addClass("custom-modal--show");
        $("body").css("overflow", "hidden");
    });

    $(document).on("click", "#http-codes-custom-modal .ir-http-codes-close", function () {
        $("#http-codes-custom-modal").removeClass("custom-modal--show").addClass("custom-modal--close");
        $("body").css("overflow", "");
    });
    /* DEFAULT SETTINGS SHOW/CLOSE --END */

    /* CUSTOM DROPDOWNS --START */

    // SETUP DROPDOWNS
    window.setupCustomDropdowns = function (id) {
        $(".custom-dropdown", document).each(function (i, v) {
            const dropdown = $(this);
            const selectedItemId = $("[data-selected-dropdown-item-id]", dropdown).attr("data-selected-dropdown-item-id");
            if (selectedItemId === "false" || selectedItemId === "[]") {
                clearDropdownSelection(dropdown);
            } else {
                updateDropdownSelection(dropdown, selectedItemId);
            }
        });

        if (Number.isInteger(id) && id > 0) {
            $(".ir-default-settings-save").attr("data-db-id", id);
        }
    }

    function clearDropdownSelection(dropdown) {
        const contentContainer = $("[data-selected-dropdown-item-id]", dropdown);
        contentContainer.text(irEventsJS.dropdown_default_message);
                    $(".custom-dropdown__li", dropdown).removeClass("custom-dropdown__li--selected");
                    $(".custom-dropdown__li", dropdown).removeClass("custom-dropdown__li--selected");
                    /* updating custom dropdown hidden input value */
        $(".custom-dropdown__li", dropdown).removeClass("custom-dropdown__li--selected");
                    /* updating custom dropdown hidden input value */
        $(".ir-custom-dropdown-value", dropdown).val("").trigger("change");
    }


    function updateDropdownSelection(dropdown, selectedItemId) {
        const contentContainer = $("[data-selected-dropdown-item-id]", dropdown);
        const isMultiple = selectedItemId === "false" ? false : JSON.parse(selectedItemId) instanceof Array;
        const dataValue = $("[data-dropdown-item-id='" + selectedItemId + "']", dropdown).attr("data-value");

        /* set a fixed width for the dropdown */
        const textElement = $(".custom-dropdown-toggle__text", dropdown);
        textElement.width((textElement.width()) + "px");

        if (isMultiple) {
            const selectedItemIds = JSON.parse(selectedItemId);
            const itemContents = [];
            clearDropdownSelection(dropdown);
            $.each(selectedItemIds, function (i, v) {
                /* add items should be selected to itemContents array */
                const itemContent = $.trim($("[data-dropdown-item-id='" + v + "']", dropdown).text());
                itemContents.push(itemContent);
                $("[data-dropdown-item-id='" + v + "']", dropdown).addClass("custom-dropdown__li--selected");
            });
            contentContainer.text(itemContents.join(", "));
        } else {
            // clearDropdownSelection(dropdown);
            const itemContent = $.trim($("[data-dropdown-item-id='" + selectedItemId + "']", dropdown).text());
            $("[data-dropdown-item-id='" + selectedItemId + "']", dropdown).addClass("custom-dropdown__li--selected");
            contentContainer.text(itemContent);
        }
        $(".ir-custom-dropdown-value", dropdown).val(dataValue);//.trigger("change");
    }

    $(document).on("change", ".ir-custom-dropdown-value", function (e) {
        const customDropdownInput = $(this);
        const customDropdownInputValue = customDropdownInput.val();
        const dropDown = customDropdownInput.parents(".custom-dropdown");
        const rowContainer = dropDown.parents(".header__flex");
        let ieDisabled = ["are-404s"];

        if (rowContainer.length) {
            if (dropDown.attr("data-name") === "criteria") {
                if (customDropdownInputValue === "are-404s") {
                    $(".ir-criteria-value", rowContainer).val("are-404s").attr("readOnly", true);
                } else if (customDropdownInputValue === "all-urls") {
                    $(".ir-criteria-value", rowContainer).val("all-urls").attr("readOnly", true);
                } else {
                    $(".ir-criteria-value", rowContainer).removeAttr("readOnly");
                }
                if (ieDisabled.includes(customDropdownInputValue)) disableAdvancedOptions();
                else enableAdvancedOptions();
            } else if (dropDown.attr("data-name") === "action") {
                if (customDropdownInputValue === "random-similar-post") {
                    $(".ir-action-value", rowContainer).val("random-similar-post").attr("readOnly", true);
                } else {
                    $(".ir-action-value", rowContainer).val("").removeAttr("readOnly");
                }
            }
        }

        /* push multiple values in array */
        if (dropDown.attr("data-multiple") === "true") {
            const selectedItems = $(".custom-dropdown__li--selected", dropDown);
            const selectedItemsValues = [];
            $.each(selectedItems, function (i, v) {
                selectedItemsValues.push($(v).attr("data-value"));
            });
            customDropdownInput.val(JSON.stringify(selectedItemsValues));
        }
    });

    window.showCustomDropdown = function () {
        $("#custom-modal").addClass("custom-modal--show");
        // $("body").css("overflow", "hidden");
    };


    window.enableAdvancedOptions = function () {
        // enabling inclusion exclusion rules switcher
        $(".ir-rules-switcher").removeAttr("onclick");

        // enabling header checkboxes
        $(".settings-box__checkboxes-container input[type=checkbox]").removeAttr("onclick");
        $(".settings-box__checkboxes-container .checkboxes-rows__text").removeAttr("onclick");
        
        // enabling click on dropdowns, checkboxes
        $(".ir-redirect-settings-container input[type=checkbox]").removeAttr("onclick");
        $(".ir-redirect-settings-container .custom-dropdown").removeAttr("onclick");
    }

    window.disableAdvancedOptions = function () {
        
        // set inclusion exclusion rules switcher > off
        $(".ir-rules-switcher").prop("checked", false).trigger("change");
        $(".ir-rules-switcher").attr("onclick", "event.preventDefault();event.stopPropagation();");
    
        $(".settings-box__checkboxes-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");
        $(".settings-box__checkboxes-container .checkboxes-rows__text").attr("onclick", "event.preventDefault();event.stopPropagation();");
        
    
        // disabling click on dropdowns, checkboxes
        $(".ir-redirect-settings-container input[type=checkbox]").attr("onclick", "event.preventDefault();event.stopPropagation();");
        $(".ir-redirect-settings-container .custom-dropdown").attr("onclick", "event.preventDefault();event.stopPropagation();");
    
    
        // hiding inclusion / exclusion container
        if (!$(".ir-redirect-settings-container").hasClass("ir-hidden")) {
            $(".ir-redirect-settings-container").addClass("ir-hidden");
        }
    }


    window.getSettingsData = function (form) {
        const obj = {};

        $.each(form.serializeArray(), function (i, v) {
            let name = $.trim(v['name']);
            const matches = name.match(/([^\[\]]+)\[([^\[\]]+)\]/);
            if (matches != null) {
                if (matches[1] && matches[2]) {
                    let nestedObj = null;

                    if (!(matches[1] in obj)) {
                        nestedObj = {};
                        obj[matches[1]] = nestedObj;
                    } else {
                        nestedObj = obj[matches[1]];
                    }

                    if (!(matches[2] in nestedObj)) {
                        nestedObj[matches[2]] = $.trim(v['value']);
                    }

                }
            } else {
                if (obj[name] === 'redirect_code' && $.trim(v['value']) === '') {
                    obj[name] = '301';
                } else {
                    obj[name] = $.trim(v['value']);
                }
            }
        });
        obj['ignore_trailing_slashes'] = form.find("input[name=ignore_trailing_slashes]").is(":checked") ? "1" : "0";
        obj['ignore_parameters'] = form.find("input[name=ignore_parameters]").is(":checked") ? "1" : "0";
        obj['ignore_case'] = form.find("input[name=ignore_case]").is(":checked") ? "1" : "0";
        obj['pass_on_parameters'] = form.find("input[name=pass_on_parameters]").is(":checked") ? "1" : "0";


        return obj;
    }

    /* handle the click on custom dropdown items and initiate custom dropdown selected item value */
    $(document).on("click", ".custom-dropdown .custom-dropdown__ul .custom-dropdown__li", function (e) {
        e.stopPropagation();

        const clickedItem = $(this);
        const newSelectedId = clickedItem.attr("data-dropdown-item-id");
        const dropdown = clickedItem.parents(".custom-dropdown");
        const dropdownContentContainer = $("[data-selected-dropdown-item-id]", dropdown);
        const dataValue = clickedItem.attr("data-value");

        /* set a fixed width for the dropdown */
        const textElement = $(".custom-dropdown-toggle__text", dropdown);
        textElement.width((textElement.width()) + "px");

        /* remove previously selected item */
        const previouslySelectedItem = $(".custom-dropdown__li--selected", dropdown);
        if (previouslySelectedItem && dropdown.attr("data-multiple") !== "true") {
            previouslySelectedItem.removeClass("custom-dropdown__li--selected");
        }

        /* highlight the selected item */
        if (clickedItem.hasClass("custom-dropdown__li--selected") === false) {
            clickedItem.addClass("custom-dropdown__li--selected");
        } else {
            clickedItem.removeClass("custom-dropdown__li--selected");
        }

        /* close the dropdown on selection */
        if (dropdown.attr("data-multiple") !== "true") {
            $(dropdown).removeClass("custom-dropdown--show");
        }

        /* updating custom dropdown hidden input value */
        $(".ir-custom-dropdown-value", dropdown).val(dataValue).trigger("change");

        /* update the dropdown text and selected id */
        if (dropdown.attr("data-multiple") === "true") {
            const selectedItems = $(".custom-dropdown__li--selected", dropdown);
            if (selectedItems.length) {
                const selectedItemsValues = [];
                $.each(selectedItems, function (i, v) {
                    selectedItemsValues.push($(v).text());
                });
                dropdownContentContainer.text(selectedItemsValues.join(", "));
            } else {
                const itemContent = irEventsJS.dropdown_default_message;
                dropdownContentContainer.text(itemContent);
            }
        } else {
            const itemContent = $(".custom-dropdown__li-content", clickedItem).text();
            dropdownContentContainer.attr("data-selected-dropdown-item-id", newSelectedId);
            dropdownContentContainer.text(itemContent);
        }
    });

    // close all dropdowns on document click
    $(document).on("click", function (e) {
        if (!($(e.target).parents(".custom-dropdown")).length) {
            $(".custom-dropdown").removeClass("custom-dropdown--show");
        }
    });

    function resetDropdownToDefault(dropdown) {
        const defaultSelected = dropdown.getAttribute("data-default-selected");
        if (defaultSelected >= 0) {
            const list = dropdown.querySelector(".custom-dropdown__ul");
            const listItem = list.querySelectorAll("li")[defaultSelected];
            const dropdownContent = dropdown.querySelector("[data-selected-dropdown-item-id]");
            dropdownContent.setAttribute("data-selected-dropdown-item-id", defaultSelected);
            dropdownContent.textContent = listItem.textContent;
            const selectedDropdownItem = dropdown.querySelector(".custom-dropdown__li--selected");
            if (selectedDropdownItem) {
                selectedDropdownItem.classList.remove("custom-dropdown__li--selected");
            }
            listItem.classList.add("custom-dropdown__li--selected");
            const dropdownValue = dropdown.querySelector(".ir-custom-dropdown-value");
            if (dropdownValue) {
                dropdownValue.value = listItem.getAttribute("data-value");
            }
        } else {
            const dropdownContent = dropdown.querySelector("[data-selected-dropdown-item-id]");
            dropdownContent.setAttribute("data-selected-dropdown-item-id", "false")
            dropdownContent.textContent = irEventsJS.dropdown_default_message;

            const selectedDropdownItem = dropdown.querySelector(".custom-dropdown__li--selected");
            if (selectedDropdownItem) {
                selectedDropdownItem.classList.remove("custom-dropdown__li--selected");
            }
        }
    }

    const specialHeaderDropdownOptions = {
        "contain": ["new-permalink-structure", "regex-match", "random-similar-post"],
        "start-with": ["urls-with-new-string", "urls-with-removed-string", "new-permalink-structure", "regex-match", "random-similar-post"],
        "end-with": ["new-permalink-structure", "regex-match", "random-similar-post"],
        "have-permalink-structure": ["a-specific-url", "urls-with-new-string", "urls-with-removed-string", "regex-match", "random-similar-post"],
        "regex-match": ["urls-with-new-string", "urls-with-removed-string", "new-permalink-structure", "random-similar-post"],
        // "day-and-name": ["day-and-name"],
        // "month-and-name": ["month-and-name"],
        // "post-name": ["post-name"],
        // "category-and-name": ["category-and-name"],
        // "author-and-name": ["author-and-name"],
        "are-404s": ["urls-with-new-string", "urls-with-removed-string", "new-permalink-structure", "regex-match"],
        "all-urls": ["urls-with-new-string", "urls-with-removed-string", "new-permalink-structure", "regex-match", "random-similar-post"],
    };

    // Display custom drop down
    $(document).on("click", ".custom-dropdown", function (e) {
        const index = $(".custom-dropdown").index($(this));
        $(".custom-dropdown").each(function (i, v) {
            if (index !== i) {
                $(v).removeClass("custom-dropdown--show");
            }
        });
        $(this).toggleClass("custom-dropdown--show");
    });

    $(document).on("click", ".header__flex-inputs .custom-dropdown", function (e) {
        var clickedDropdown = $(this)[0].closest(".custom-dropdown");
        var rowContainer = clickedDropdown.closest(".header__flex");

        /* reset all doropdown items to default */
        rowContainer.querySelectorAll(".custom-dropdown__li--disabled").forEach(function (el) {
            el.classList.remove("custom-dropdown__li--disabled");
            el.removeAttribute("onclick");
        });

        const criteriaContainer = document.querySelector(".ir-criterias .header__flex-inputs");
        var criteriaDropdown = rowContainer.querySelectorAll(".custom-dropdown")[0];
        var criteriaPermalinkDropdown = $(".custom-dropdown", rowContainer)[1];
        const actionDropdown = rowContainer.querySelectorAll(".custom-dropdown")[2];
        const actionPermalinkDropdown = rowContainer.querySelectorAll(".custom-dropdown")[3];

        /* disabling criteria permalink value dropdown item that has the current permalink structure */
        if (clickedDropdown === criteriaPermalinkDropdown) {
            var dropdownListItem = clickedDropdown.querySelector('.custom-dropdown__li[data-value="' + irEventsJS.permalinkStructure + '"]')
            dropdownListItem.classList.add("custom-dropdown__li--disabled");
            dropdownListItem.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
        }


        if (clickedDropdown === actionDropdown) {
            var criteriaValue = $("input[type='hidden']", criteriaDropdown).val();
            if (specialHeaderDropdownOptions.hasOwnProperty(criteriaValue)) {
                specialHeaderDropdownOptions[criteriaValue].forEach(function (value) {
                    var dropdownListItem = clickedDropdown.querySelector('.custom-dropdown__li[data-value="' + value + '"]')
                    dropdownListItem.classList.add("custom-dropdown__li--disabled");
                    dropdownListItem.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
                })
            }
        }

        /* disabling same values if selected "have permalink structure" => "new permalink structure" */
        if (clickedDropdown === actionPermalinkDropdown) {
            var criteriaPermalinkInput = $('input[type="hidden"]', criteriaPermalinkDropdown);
            if (criteriaPermalinkInput.length) {
                var criteriaPermalinkValue = criteriaPermalinkInput.val();
                // if (specialHeaderDropdownOptions.hasOwnProperty(criteriaPermalinkValue)) {
                //     specialHeaderDropdownOptions[criteriaPermalinkValue].forEach(function (value) {
                //         var dropdownListItem = clickedDropdown.querySelector('.custom-dropdown__li[data-value="' + value + '"]');
                //         dropdownListItem.classList.add("custom-dropdown__li--disabled");
                //         dropdownListItem.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
                //     });
                // }
                $(".custom-dropdown__li", clickedDropdown).each(function (i, v) {
                    if (v.getAttribute("data-value") !== irEventsJS.permalinkStructure) {
                        v.classList.add("custom-dropdown__li--disabled");
                        v.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
                    }
                });
                // var dropdownListItem = clickedDropdown.querySelector('.custom-dropdown__li[data-value="' + irEventsJS.permalinkStructure + '"]')
                // dropdownListItem.classList.add("custom-dropdown__li--disabled");
                // dropdownListItem.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
            }
        }

        $(".custom-dropdown__li", clickedDropdown).each(function (i, v) {
            if (v.getAttribute("data-status") === "disabled") {
                v.classList.add("custom-dropdown__li--disabled");
                v.setAttribute("onclick", "event.preventDefault();event.stopPropagation();");
            }
        });

//        $(this).toggleClass("custom-dropdown--show");
    });

    function openOptionsExplanation() {
        document.querySelector(".header__popup").classList.remove("header__popup--hidden");
    }

    function closeOptionsExplanation() {
        var headerPopup = document.querySelector(".header__popup");
        if (headerPopup && !headerPopup.classList.contains("header__popup--hidden")) {
            headerPopup.classList.add("header__popup--hidden");
        }
    }

    $(document).on("click", ".ir-header-popup-close", function (e) {
        closeOptionsExplanation()

        var headerHeading = document.querySelector(".header__heading")
        window.scrollTo({
            top: headerHeading.offsetTop,
            behavior: "smooth"
        })
    })

    const customDropdownActions = {
        "regex-match": function (clickedItem) {
            var dropdown = clickedItem.closest(".custom-dropdown");
            var input = dropdown.parentNode.querySelector(".input-group__input");
            if (input) {
                input.style.display = '';
                input.setAttribute("placeholder", irEventsJS.regex_placeholder);
            }
        },

        "a-specific-url": function (clickedItem) {
            var dropdown = clickedItem.closest(".custom-dropdown");
            var input = dropdown.parentNode.querySelector(".input-group__input");
            if (input) {
                input.style.display = '';
                input.setAttribute("placeholder", irEventsJS.enter_the_url_placeholder);
            }
        },

        "explain-those-options": function () {
            openOptionsExplanation()
        },

        resetDropdownToDefault: function (clickedItem) {
            /* Reset placeholder */
            var dropdown = clickedItem.closest(".custom-dropdown");
            var input = dropdown.parentNode.querySelector(".input-group__input");
            if (input) {
                input.style.display = '';
                input.setAttribute("placeholder", irEventsJS.enter_the_string_placeholder);
            }
        }
    }

    $(document).on("click", ".header__flex .custom-dropdown .custom-dropdown__ul .custom-dropdown__li", function (e) {
        e.stopPropagation();

        const clickedItem = $(this);
        const newSelectedId = clickedItem.attr("data-dropdown-item-id");
        const dropdown = clickedItem.parents(".custom-dropdown");
        const dropdownContentContainer = $("[data-selected-dropdown-item-id]", dropdown);
        var rowContainer = dropdown.parents(".header__flex");

        var dataValue = clickedItem.attr("data-value");

        const criteriaDropdown = $(".custom-dropdown", rowContainer)[0];
        const criteriaPermalinkDropdown = $(".custom-dropdown", rowContainer)[1];
        const actionDropdown = $(".custom-dropdown", rowContainer)[2];
        const actionPermalinkDropdown = $(".custom-dropdown", rowContainer)[3];

        if (dataValue === "have-permalink-structure") {
            $(".header__2nd-dropdown-container.ir-criteria-value-dd", rowContainer).addClass("header__2nd-dropdown-container--active");
            $("input[name=criteria_value_dd]", rowContainer).removeAttr("disabled");
            $("input[name=criteria_value]", rowContainer).attr("disabled", true).val("");
        } else if (criteriaDropdown === clickedItem[0].closest(".custom-dropdown")) {
            const criteriaValueDropdown = clickedItem[0].closest(".header__flex").querySelectorAll(".custom-dropdown")[1];
            $(".header__2nd-dropdown-container.ir-criteria-value-dd", rowContainer).removeClass("header__2nd-dropdown-container--active");
            $("input[name=criteria_value_dd]", rowContainer).attr("disabled", true).val("");
            if (dataValue !== "are-404s") {
                $("input[name=criteria_value]", rowContainer).removeAttr("disabled");
            }
        }

        if (dataValue === "new-permalink-structure") {
            $(".header__2nd-dropdown-container.ir-action-value-dd", rowContainer).addClass("header__2nd-dropdown-container--active");
            $("input[name=action_value_dd]", rowContainer).removeAttr("disabled");
            $("input[name=action_value]", rowContainer).attr("disabled", true);
        } else if (actionDropdown === clickedItem[0].closest(".custom-dropdown")) {
            $(".header__2nd-dropdown-container.ir-action-value-dd", rowContainer).removeClass("header__2nd-dropdown-container--active");
            $("input[name=action_value_dd]", rowContainer).attr("disabled", true);
            $("input[name=action_value]", rowContainer).removeAttr("disabled");
        }


        if (customDropdownActions.hasOwnProperty(dataValue)) {
            customDropdownActions[dataValue](clickedItem[0]);

            if (dataValue === "explain-those-options") {
                /* close the dropdown */
                $(dropdown).removeClass("custom-dropdown--show");
                return;
            }

        } else {
            customDropdownActions.resetDropdownToDefault(clickedItem[0]);
        }

        closeOptionsExplanation();

        /* set a fixed width for the dropdown */
        const textElement = $(".custom-dropdown-toggle__text", dropdown);
        textElement.width(textElement.width() + "px");

        $(".ir-redirect-code", dropdown).val(dataValue);

        // /* update the dropdown text and selected id */
        // const itemContent = $(".custom-dropdown__li-content", clickedItem).text();
        // dropdownContentContainer.attr("data-selected-dropdown-item-id", newSelectedId);
        // dropdownContentContainer.text(itemContent);
        //
        // /* remove previously selected item */
        // const previouslySelectedItem = $(".custom-dropdown__li--selected", dropdown);
        // if (previouslySelectedItem) {
        //     previouslySelectedItem.removeClass("custom-dropdown__li--selected");
        // }
        //
        // /* highlight the selected item */
        // clickedItem.addClass("custom-dropdown__li--selected");
        //
        // /* close the dropdown on selection */
        // $(dropdown).removeClass("custom-dropdown--show");

        var firstRulesRow = document.querySelector('#ir_hedaer_flex')

        if (
                /* The first row is the only one that contains a dropdown and input in the right side */
                firstRulesRow === dropdown[0].closest('.header__flex') &&
                /* Check if the current dropdown is located in the left side */
                criteriaDropdown === dropdown[0]
                ) {
            var dropdownInTheRight = $(".custom-dropdown", rowContainer)[2];

            var inputInTheRight = $(".input-group__input", rowContainer)[1];
            /* reset right dropdown to default */
            resetDropdownToDefault(dropdownInTheRight);

            inputInTheRight.setAttribute("placeholder", irEventsJS.enter_the_url_placeholder);
        } else if (criteriaPermalinkDropdown === dropdown[0] && actionPermalinkDropdown) {
            resetDropdownToDefault(actionPermalinkDropdown);
        }

        if ($(".header__flex-inputs--disabled", rowContainer)) {
            // TODO ???
            // $(".header__flex-inputs--disabled", rowContainer).find( "input" ).prop( "disabled", false );
            $(".header__flex-inputs--disabled", rowContainer).removeClass("header__flex-inputs--disabled");
        }

        /* Remove input for urls-with-removed-string */
        if (dataValue === 'urls-with-removed-string') {
            document.querySelector('input[name="action_value"]').style.display = 'none';
            //$("input[name=action_value]", rowContainer).attr("disabled", true);
        } else {
            document.querySelector('input[name="action_value"]').style.display = '';
            //$("input[name=action_value]", rowContainer).removeAttr("disabled");
        }

    });
    /* CUSTOM DROPDOWN --END */

    /* RULES SECTION SHOW/HIDE --START */
    showHideContainer(".ir-rules-switcher", ".ir-redirect-settings-container");

    $(document).on("change", ".ir-rules-switcher", function () {
        showHideContainer(".ir-rules-switcher", ".ir-redirect-settings-container");
    });

    $(document).on("click", ".ir-rules-switcher, .custom-switch", function () {
        const criteriaDD = $("#ir_hedaer_flex .ir-criterias .custom-dropdown")[0];
        const criteriaDDValue = $(".ir-custom-dropdown-value", criteriaDD);
        if (criteriaDDValue) {
            if (criteriaDDValue.val() === "are-404s") {
                disableAdvancedOptions();
                notify({autoCloseAfter: 3000, type: 'error', heading: 'Error', text: irEventsJS.notify_3000_05});
            }

        }
    });

    /* RULES SECTION SHOW/HIDE --END */

    function showHideContainer(switcher, container) {
        if ($(switcher).is(":checked")) {
            $(container).removeClass("ir-hidden");
        } else {
            $(container).addClass("ir-hidden");
        }
    }

    $(document).on("submit", ".ir-live-search-form, .ir-rules-form", function (e) {
        e.preventDefault();
        return;
    });

    $(document).on("click", ".ir-live-search-btn", function (e) {
        e.preventDefault();
        return;
    });


    $(".ir-reload-clear").val("");

    $(document).on("click", ".ir-add-another-criteria", function (e) {
        e.preventDefault();

        const rulesContainer = $(".ir-rules-container");
        var clonedHeaderFlexPlaceholder = document.querySelector(".ir-header-flex-as-placeholder").cloneNode(true);

        if (window.innerWidth >= 1140) {
            rulesContainer.append(clonedHeaderFlexPlaceholder);
        } else {
            var arrowSvg = document.querySelector('#ir_hedaer_flex .header__arrow-svg')
            arrowSvg.parentNode.insertBefore(clonedHeaderFlexPlaceholder, arrowSvg)
        }

        setTimeout(() => {
            clonedHeaderFlexPlaceholder.classList.remove("ir-header-flex-as-placeholder");
            clonedHeaderFlexPlaceholder.classList.remove("header__flex--hidden-placeholder");

            // Show the cancel button
            document.querySelector('.cta__cancel-btn').classList.add('cta__cancel-btn--show');
        }, 10);
    });

    $(document).on("click", ".note-item__btn-open", function (e) {
        const noteBtn = $(this);
        const noteItem = noteBtn.parents(".note-item");
        // const hiddenContent = noteItem.querySelector('.note-item-content');
        const noteBtnText = $(".note-item__title-text", noteItem);

        if (noteItem.hasClass("note-item--open")) {
            noteItem.removeClass("note-item--open");
            noteBtnText.text("Show intro");
        } else {
            noteItem.addClass("note-item--open");
            noteBtnText.text("Hide intro");
        }
    });

    window.irScrollToRight = function () {
        const elems = document.querySelectorAll(".ir-scroll-to-right");
        elems.forEach((el, i, elems) => {
            el.scrollLeft = el.scrollWidth;
        });
    };

    irScrollToRight();

    window.irHideCancelBtn = function () {
        const cancelBtn = document.querySelector(".ir-header-cancel.cta__cancel-btn--show");
        if (cancelBtn) {
            cancelBtn.classList.remove("cta__cancel-btn--show");
        }
    };

    irHideCancelBtn();


    /**
     * if default settings checkboxes are checked, sets selected the first value of a dropdown in the current row
     */
    $(document).on("click", ".ir-default-settings-form .rules-table__checkbox", function (e) {
        const elem = $(this);
        const parentRow = elem.parents('.rules-table__row');
        const dropdownsInRow = $(".custom-dropdown", parentRow);

        $.each(dropdownsInRow, function (i, dropdown) {
            if (elem.is(":checked")) {
                const currentSelected = $("[data-selected-dropdown-item-id]", dropdown).attr("data-selected-dropdown-item-id");
                // console.log(currentSelected);
                if (currentSelected === "false") {
                    $("[data-selected-dropdown-item-id]", dropdown).attr("data-selected-dropdown-item-id", 0);
                }
            } else {
                $("[data-selected-dropdown-item-id]", dropdown).attr("data-selected-dropdown-item-id", "false");
            }
        });

        setupCustomDropdowns();
    });

    $(document).on("click", ".ir-confirm-uninstall", function (e) {
        if (!confirm(irEventsJS.confirm_message)) {
            e.preventDefault();
            return;
        }
    });

    $(document).on("change", ".ir-custom-dropdown-value.ir-criteria", function (e) {
        const regexHelpCriteria = document.querySelector('.ir-criterias .regex-help');

        if (regexHelpCriteria.classList.contains('show') && this.value !== 'regex-match' && this.value != '') {
            regexHelpCriteria.classList.remove('show');
        } else if (!regexHelpCriteria.classList.contains('show') && this.value === 'regex-match') {
            regexHelpCriteria.classList.add('show');
        }
    });

    // Default options listener
    (function () {
      
      if (document.querySelector('.settings-box__container') == null) return;

      // Relevant elements
      const $advancedSettingsBox = document.querySelector('.settings-box__container'); // Main box containing all checkboxes
      const $checkboxes = $advancedSettingsBox.querySelectorAll('input[type="checkbox"]:not(.redi_nondef)'); // default: all unchecked
      const $input = $advancedSettingsBox.querySelector('input[name="redirection_http_headers"]'); // default: empty
      const $redirectType = $advancedSettingsBox.querySelector('input[name="redirect_code"]'); // default: 301

      // Return if at least one of these elements does not exist in DOM
      if (!($advancedSettingsBox && $checkboxes && $input && $redirectType)) return;

      // Update tailored/default text
      // * tailoerd: if true set it to tailored text
      let updateText = (tailored = true) => {
        document.querySelector('#ir-default-settings-text').style.display = tailored ? 'none' : 'block';
        document.querySelector('#ir-tailored-settings-text').style.display = tailored ? 'block' : 'none';
      }

      // Default values checker
      let checkDefaultValues = (e) => {
        if ($redirectType.value != '301') return updateText();
        else if ($input.value.trim() != '') return updateText();
        else if ($advancedSettingsBox.querySelectorAll('input[type="checkbox"]:not(.redi_nondef):checked').length > 0) return updateText();
        else return updateText(false);
      }

      // Event listeners
      $($checkboxes).on('change', checkDefaultValues);
      $($input).on('change', checkDefaultValues);
      $($redirectType).on('change', checkDefaultValues);

      // After site update, wait for all requests to finish and update the text accordingly <= 10ms.
      setTimeout(checkDefaultValues);

    })();

    function initializeTooltips() {
        $('.ir-instant-edit-from, .ir-instant-edit-to').tooltipster({
            theme: 'tooltipster-sidetip',
            animation: 'fade',
            delay: 200,
            trigger: 'hover',
            interactive: true, 
            functionFormat: function(instance, helper, content) {
                return $(helper.origin).val();
            }
        });
    }

    initializeTooltips();

    $(document).ajaxComplete(function() {
        initializeTooltips();
    });

});
