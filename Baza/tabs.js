/**
 * Created by Home on 01.05.2017.
 */
// http://htmlhook.ru/prostye-vkladki-tab-dlya-sajta.html

/*
 * aria-pixel-tabs.js ANSI
 * Copyright (C) ������ ���� <htmlhook.ru/>
 * Version: 1.0
 * �������������: 29 ���� 2015�.
 */
var tabList = document.getElementById("pixel-tabs") // ������ ��� ul
tabList.setAttribute("role", "tablist"); // ���� ��� ul
var roles = tabList.getElementsByTagName("*"); // ���� ��� ���� li
for(var li = 0; li < roles.length; li++){
    roles[li].setAttribute("role","presentation");
}
var roles = tabList.getElementsByTagName("a"); // ���� ��� ���� ������ � ul
for(var a = 0; a < roles.length; a++){
    roles[a].setAttribute("role","tab");
}
var oneTab = document.getElementById("one-tab") // ������ ��� ������� ����
// �������� ��� ������� ����
oneTab.setAttribute("aria-selected", "true");
oneTab.setAttribute("aria-controls", "content-one");
oneTab.setAttribute("tabindex", "1");  // ��� ��������� ����
var twoTab = document.getElementById("two-tab") // ������ ��� ������� ����
// �������� ��� ������� ����
twoTab.setAttribute("aria-selected", "false");
twoTab.setAttribute("aria-controls", "content-two");
twoTab.setAttribute("tabindex", "2");  // ��� �� ��������� ����
var contentTab = document.getElementById("content-region") // ������ ��� ����� � �����������
contentTab.setAttribute("aria-live", "polite");
contentTab.setAttribute("role", "region");
var roles = contentTab.getElementsByTagName("div"); // ���� � ���������� ��� ����������
for(var d = 0; d < roles.length; d++){
    roles[d].setAttribute("role","tabpanel") || roles[d].setAttribute("tabindex","0");
}
var contentOne = document.getElementById("content-one"); // ������ ��� ���������� 1
// ��� ���������� ����������
contentOne.setAttribute("aria-hidden", "false");
contentOne.setAttribute("aria-labelledby", "content-one");
var contentTwo = document.getElementById("content-two"); // ������ ��� ���������� 2
// ��� �������� ���������
contentTwo.setAttribute("aria-hidden", "true");
contentTwo.setAttribute("aria-labelledby", "content-two");
document.querySelector('#one-tab').onclick = function(){ // ���� ����� ������ ���
// ����
    oneTab.setAttribute("aria-selected", "true"); // ��� �������� ������� ����
    twoTab.setAttribute("aria-selected", "false"); // ��� �� �������� ������� ����
    oneTab.setAttribute("tabindex", "1");  // ��� ��������� ����
    twoTab.setAttribute("tabindex", "2"); // ��� �� ��������� ����
// ����������
    contentOne.setAttribute("aria-hidden", "false"); // ��� ����������� ����������
    contentTwo.setAttribute("aria-hidden", "true"); // ��� �������� ����������
    return false;
}
document.querySelector('#two-tab').onclick = function(){ // ���� ����� ������ ���
// ����
    oneTab.setAttribute("aria-selected", "false"); // ��� �� �������� ������� ����
    twoTab.setAttribute("aria-selected", "true"); // ��� �������� ������� ����
    oneTab.setAttribute("tabindex", "2"); // ��� �� ��������� ����
    twoTab.setAttribute("tabindex", "1"); // ��� ��������� ����
// ����������
    contentOne.setAttribute("aria-hidden", "true"); // ��� �������� ����������
    contentTwo.setAttribute("aria-hidden", "false"); // ��� ����������� ����������
    return false;
}
