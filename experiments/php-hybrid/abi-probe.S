.text
.globl probe_autop_r14
.type probe_autop_r14,@function
probe_autop_r14:
    pushq %rbp
    pushq %rbx
    pushq %r12
    pushq %r13
    pushq %r14
    pushq %r15
    subq $8,%rsp
    movabsq $0x123456789abcdef0,%r14
    call ep_autop@PLT
    movq observed_r14@GOTPCREL(%rip),%r10
    movq %r14,(%r10)
    addq $8,%rsp
    popq %r15
    popq %r14
    popq %r13
    popq %r12
    popq %rbx
    popq %rbp
    ret
.size probe_autop_r14,.-probe_autop_r14
.text
.globl probe_serialize_r14
.type probe_serialize_r14,@function
probe_serialize_r14:
    pushq %rbp
    pushq %rbx
    pushq %r12
    pushq %r13
    pushq %r14
    pushq %r15
    subq $8,%rsp
    movabsq $0x123456789abcdef0,%r14
    call ep_serialize_json@PLT
    movq observed_r14@GOTPCREL(%rip),%r10
    movq %r14,(%r10)
    addq $8,%rsp
    popq %r15
    popq %r14
    popq %r13
    popq %r12
    popq %rbx
    popq %rbp
    ret
.size probe_serialize_r14,.-probe_serialize_r14
.section .note.GNU-stack,"",@progbits
