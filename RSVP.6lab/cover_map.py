#!/usr/bin/env python3
"""Theme map: running book number (1..120, the order of books.js) -> a themed SVG
cover from img/covers/. Used by build_books.py to place on every book page a
picture associated with the work described on that page."""

COVERS = {
    # ---- Antiquity 1-43 ----
    1: 'epic', 2: 'epic',                                   # Homer: Iliad, Odyssey
    3: 'tragedy', 4: 'tragedy',                              # Aeschylus
    5: 'tragedy', 6: 'tragedy', 7: 'tragedy', 8: 'tragedy',  # Sophocles
    9: 'history', 10: 'history',                             # Herodotus, Thucydides
    11: 'tragedy', 12: 'tragedy', 13: 'tragedy',             # Euripides
    14: 'comedy', 15: 'comedy',                              # Aristophanes
    16: 'philosophy', 17: 'philosophy', 18: 'philosophy',   # Plato: Apology, Crito, Phaedo
    19: 'love', 20: 'justice', 21: 'philosophy', 22: 'rhetoric', 23: 'astronomy',  # Symposium, Republic, Meno, Gorgias, Timaeus
    24: 'ethics', 25: 'politics', 26: 'tragedy', 27: 'science', 28: 'metaphysics', # Aristotle: Ethics, Politics, Poetics, Physics, Metaphysics
    29: 'math',                                             # Euclid
    30: 'ethics', 31: 'politics',                           # Cicero
    32: 'atom',                                             # Lucretius
    33: 'epic',                                             # Virgil
    34: 'lyric',                                            # Horace
    35: 'myth',                                             # Ovid
    36: 'history',                                          # Plutarch
    37: 'philosophy',                                       # Epictetus
    38: 'history',                                          # Tacitus
    39: 'meditation',                                       # Marcus Aurelius
    40: 'astronomy',                                        # Ptolemy
    41: 'light',                                            # Plotinus
    42: 'confession', 43: 'two-cities',                     # Augustine
    # ---- Middle Ages & Renaissance 44-57 ----
    44: 'epic', 45: 'epic',                                 # Roland, Beowulf
    46: 'summa',                                            # Aquinas
    47: 'divine', 48: 'love',                               # Dante
    49: 'lyric',                                            # Petrarch
    50: 'pilgrim',                                          # Chaucer
    51: 'prince', 52: 'republic',                           # Machiavelli
    53: 'folly',                                            # Erasmus
    54: 'utopia',                                           # Thomas More
    55: 'comedy',                                           # Rabelais
    56: 'theology',                                         # Calvin
    57: 'essay',                                            # Montaigne
    # ---- 17th-18th c.: Science & Enlightenment 58-85 ----
    58: 'ghost', 59: 'tragedy', 60: 'witch', 61: 'jealousy', 62: 'sonnet',   # Shakespeare
    63: 'donquixote',                                       # Cervantes
    64: 'science', 65: 'utopia',                            # Bacon
    66: 'method', 67: 'doubt',                              # Descartes
    68: 'leviathan',                                        # Hobbes
    69: 'paradise', 70: 'freedom',                          # Milton
    71: 'heart',                                            # Pascal
    72: 'ethics',                                           # Spinoza
    73: 'liberty', 74: 'mind',                              # Locke
    75: 'gravity',                                          # Newton
    76: 'travel',                                           # Swift
    77: 'decameron',                                        # Voltaire (Candide)
    78: 'nature',                                           # Hume
    79: 'contract', 80: 'education',                        # Rousseau
    81: 'capital',                                          # Adam Smith
    82: 'pure-reason', 83: 'conscience',                    # Kant
    84: 'federalist',                                       # Hamilton/Madison/Jay
    85: 'rights',                                           # Wollstonecraft
    # ---- 19th century 86-105 ----
    86: 'faust',                                            # Goethe
    87: 'novel', 88: 'novel',                               # Austen
    89: 'spirit',                                           # Hegel
    90: 'american',                                         # Tocqueville
    91: 'leap',                                             # Kierkegaard
    92: 'capital', 93: 'manifesto',                         # Marx, Marx+Engels
    94: 'liberty', 95: 'utilitarianism',                    # Mill
    96: 'origin',                                           # Darwin
    97: 'whale',                                            # Melville
    98: 'guilt', 99: 'brothers',                            # Dostoevsky
    100: 'war', 101: 'passion',                             # Tolstoy
    102: 'river',                                           # Twain
    103: 'zarathustra', 104: 'beyond', 105: 'morals',       # Nietzsche
    # ---- 20th century 106-120 ----
    106: 'dreams', 107: 'neurosis',                         # Freud
    108: 'protestant',                                      # Weber
    109: 'epic', 110: 'portrait',                           # Joyce
    111: 'lighthouse', 112: 'room',                         # Woolf
    113: 'trial', 114: 'insect',                            # Kafka
    115: 'being',                                           # Heidegger
    116: 'absurd', 117: 'sisyphus',                         # Camus
    118: 'nothingness',                                     # Sartre
    119: 'nineteen',                                        # Orwell
    120: 'totalitarian',                                    # Arendt
}

def cover_for(num: int) -> str:
    return COVERS.get(num, 'book')
